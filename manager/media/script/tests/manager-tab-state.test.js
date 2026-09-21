const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const state = require('../manager-tab-state.js');
const options = {base:'/manager/',origin:'https://example.test',token:'current-token'};
const noMenu = {querySelectorAll:()=>[]};

test('navigation addresses and parameters round-trip without registration or allowlists',()=>{
    for(const input of [
        '?a=27&r=1&id=30&opened=2|25|', '?a=3&r=1&id=30&opened=',
        '?a=4&pid=2', '?a=72&pid=2', '?a=99&page=2&search=Test&role=1',
        '?a=86&tab=2', '?a=76&tab=4', '?a=450&custom=value',
        '?a=112&id=commerce&get=orders&status=1,2&search=Тест',
        '?a=112&id=docs&get=docs', '/ssettings/configure?a=2500&tab=notifications',
        '/new-package/nested/42?filter[]=one&filter[]=two', '/module/?q=hello#section'
    ]){
        const expected = new URL(input, options.origin+options.base);
        const saved=state.remember(input, options);
        assert.ok(saved,input);
        const plan=state.resume({...saved,title:'Page'},options,noMenu);
        const restored=new URL(plan.url,options.origin+options.base);
        assert.equal(restored.pathname,expected.pathname,input);
        assert.equal(restored.hash,expected.hash,input);
        for(const key of new Set(expected.searchParams.keys())){
            assert.deepEqual(restored.searchParams.getAll(key),expected.searchParams.getAll(key),input);
        }
        assert.equal(restored.searchParams.get('_token'),'current-token');
        assert.equal(plan.manualModule,undefined);
    }
});

test('old tokens and method overrides are not persisted; restoration is a fresh GET',()=>{
    const saved=state.storage('?a=27&r=1&id=30&_token=old&_token=older&_method=POST',options);
    assert.equal(saved,'?a=27&r=1&id=30');
    assert.equal(state.restore(saved,options),'?a=27&r=1&id=30&_token=current-token');
    assert.equal(state.restore(saved,{...options,token:''}),'');
    assert.equal(state.restore(saved,{...options,token:'rotated'}),'?a=27&r=1&id=30&_token=rotated');
});

test('the manager hash and index.php links resolve to the same document',()=>{
    for(const input of ['#?a=27&r=1&id=30','index.php?a=27&r=1&id=30','https://example.test/manager/#?a=27&r=1&id=30']){
        assert.equal(state.storage(input,options),'?a=27&r=1&id=30');
    }
});

test('never sends the session token to another origin or non-HTTP address',()=>{
    for(const url of ['javascript:alert(1)','data:text/html,test','https://evil.test/manager/?a=27','//evil.test/module','https://user:pass@example.test/manager/?a=27','http://example.test/manager/?a=27']){
        assert.equal(state.storage(url,options),'');
        assert.equal(state.restore(url,options),'');
    }
});

test('stored title HTML is sanitized, and ID-only state migrates from the current menu',()=>{
    assert.equal(state.title('<script>alert(1)</script><img onerror=evil()>Document'),'Document');
    assert.equal(state.title('<i class="fa fa-file"></i> Document'),'<i class="fa fa-file"></i> Document');
    const menu={querySelectorAll:()=>[{getAttribute:()=>'/ssettings?a=2500',innerHTML:'Налаштування'}]};
    assert.equal(state.resume({module:'old-menu-id',title:'Налаштування'},options,menu).url,'/ssettings?a=2500&_token=current-token');
    assert.equal(state.resume({module:'missing'},options,noMenu),null);
});

for(const [theme,name] of [['default','evo']]){
    test(theme+': actual tree navigation, storage and reload preserve document and neighbouring tabs',()=>{
        const manager={config:{},extended(methods){Object.assign(this,methods);}};
        const tabs=[];
        let selected=null;
        const row={querySelectorAll:()=>tabs,querySelector:()=>selected};
        const node={firstChild:{dataset:{treepageclick:'27',openfolder:'0',showchildren:'0',titleEsc:'Угода користувача'}}};
        const doc={
            baseURI:options.origin+options.base,
            getElementById:id=>id==='node30'?node:null,
            querySelector:s=>s.includes('csrf-token')?{getAttribute:()=>options.token}:row,
            querySelectorAll:()=>[],addEventListener(){}
        };
        const values=new Map();
        const storage={getItem:k=>values.get(k)||null,setItem:(k,v)=>values.set(k,v),removeItem:k=>values.delete(k)};
        const tree={ca:'open'};
        const win={tree,location:{origin:options.origin},localStorage:storage,evoManagerTabState:state,addEventListener(){}};
        const sandbox={window:win,document:doc,tree,navigator:{userAgent:'',appVersion:''},Element:function(){},[name]:manager,localStorage:storage,URL,console};
        vm.createContext(sandbox);
        vm.runInContext(fs.readFileSync(path.join(__dirname,'../../style',theme,'js',name+'.js'),'utf8'),sandbox);
        manager.config={global_tabs:true}; // Deliberately no restoration registries.
        manager.EVO_MANAGER_URL=manager.MODX_MANAGER_URL=options.origin+options.base;
        manager.openedArray=[];
        manager.openedArray[2]=true;
        manager.openedArray[25]=true;
        manager.tree.setSelected=()=>{};
        const navigate=plan=>{
            const tab={id:'evo-tab-'+tabs.length,dataset:{url:plan.url,title:plan.title}};
            tabs.push(tab);selected=tab;
        };
        manager.tabs=navigate;
        navigate({url:'?a=112&id=commerce&get=orders',title:'Комерція'});
        navigate({url:'?a=112&id=docs&get=docs',title:'Документація'});
        manager.tree.treeAction({ctrlKey:false,shiftKey:false,preventDefault(){}},30,'Угода користувача');
        assert.match(selected.dataset.url,/\?a=27&r=1&id=30&opened=2\|25\|$/);
        const original=selected.dataset.url;
        manager.tabsStore();
        const snapshot=JSON.parse(values.get(manager.tabsStorageKey));
        assert.equal(snapshot.tabs.length,3);
        assert.equal(new URL(snapshot.active.url,options.origin+options.base).searchParams.get('id'),'30');
        const opened=[];
        manager.tabs=plan=>opened.push(plan);
        assert.equal(manager.tabsRestore(),true);
        assert.equal(opened.length,4);
        assert.ok(opened[0].url.includes('id=commerce'));
        assert.ok(opened[1].url.includes('id=docs'));
        assert.equal(opened.at(-1).activate,true);
        assert.equal(opened.at(-1).url,state.restore(original,options));
        assert.equal(new URL(opened.at(-1).url,options.origin+options.base).searchParams.get('opened'),'2|25|');
        // The same loaded core method also opens document details, not just the editor.
        manager.tabs=navigate;
        node.firstChild.dataset.treepageclick='3';
        node.firstChild.dataset.openfolder='1';
        manager.tree.treeAction({ctrlKey:false,shiftKey:false,preventDefault(){}},30,'Угода користувача');
        assert.equal(state.storage(selected.dataset.url,options),'?a=3&r=1&id=30');

        for(const method of ['get','post']){
            const headers={};
            manager.XHR=()=>({open(){},setRequestHeader:(k,v)=>headers[k]=v,send(){}});
            manager[method]('?a=67');
            assert.equal(headers['X-CSRF-TOKEN'],options.token);
            delete headers['X-CSRF-TOKEN'];
            manager[method]('https://other.test/');
            assert.equal(headers['X-CSRF-TOKEN'],undefined);
        }
    });
}
