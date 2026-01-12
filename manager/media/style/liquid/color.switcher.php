<div class="evocp-box">
    <div class="evocp-icon"><i class="evocpicon <?= $_style['icon_brush'] ?>" aria-hidden="true" ></i></div>
    <div class="evocp-frame">
    <h2 >COLOR SWITCHER</h2>
    <div class="panel panel-default">
     <div class="panel-heading">
      <h3 data-toggle="collapse" data-target=".bgmcolors"><i class="togglearrow <?= $_style['icon_chevron_down'] ?>" aria-hidden="true"></i> <i class="<?= $_style['icon_bars'] ?> aria-hidden="true"></i> Menu Background</h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadAll('my_evo_bgmcolor')" class="pull-right resetcolor btn btn-secondary"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
         </div>
        <div class="panel-body collapse in bgmcolors">
        <div class="evocp-bgmcolors">
        <div class="evocp-bgmcolor">#000</div>
        <div class="evocp-bgmcolor">#222</div>
        <div class="evocp-bgmcolor">#333</div>
        <div class="evocp-bgmcolor">#444</div>
        <div class="evocp-bgmcolor">#555</div>
        <div class="evocp-bgmcolor">#777</div>
        <div class="evocp-bgmcolor">#888</div>
        <div class="evocp-bgmcolor">#0f243e</div>
        <div class="evocp-bgmcolor">#548dd4</div>
        <div class="evocp-bgmcolor">#134f5c</div>
        <div class="evocp-bgmcolor">#0b5394</div>
        <div class="evocp-bgmcolor">#351c75</div>
        <div class="evocp-bgmcolor">#741b47</div>
        <div class="evocp-bgmcolor">#900</div>
        </div>
      <input type="color" class="color" id="bgmPicker" name="evocpCustombgmColor" value="#cf2626" placeholder="color code...">
       </div>
          </div>
        <div class="panel panel-default">
        <div class="panel-heading">
            <h3 span data-toggle="collapse" data-target=".menuColors"><i class="togglearrow <?= $_style['icon_chevron_right'] ?>" aria-hidden="true"></i> <i class="<?= $_style['icon_bars'] ?>" aria-hidden="true"></i> Menu links</h3> <a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_menuColor')" class="pull-right resetcolor btn btn-secondary"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
        </div>
        <div class="panel-body collapse menuColors">
        <div class="evocp-menuColors">
        <div class="evocp-menuColor">#000</div>
        <div class="evocp-menuColor">#222</div>
        <div class="evocp-menuColor">#555</div>
        <div class="evocp-menuColor">#666</div>
        <div class="evocp-menuColor evocp_light">#dedede</div>
        <div class="evocp-menuColor evocp_light">#fafafa</div>
        <div class="evocp-menuColor evocp_light">#fff</div>
        <div class="evocp-menuColor">#b45f06</div>
        <div class="evocp-menuColor">#38761d</div>
        <div class="evocp-menuColor">#134f5c</div>
        <div class="evocp-menuColor">#0b5394</div>
        <div class="evocp-menuColor">#351c75</div>
        <div class="evocp-menuColor">#741b47</div>
        <div class="evocp-menuColor">#9d2661</div>
        </div>
       <input class="color" type="color" id="menucolorPicker" name="evocpCustommenuColor" value="#cf2626" placeholder="color code...">
        </div>
        </div>

        <div class="panel panel-default">
        <div class="panel-heading">
        <h3 data-toggle="collapse" data-target=".menuHColors"><i class="togglearrow <?= $_style['icon_chevron_right'] ?>" aria-hidden="true"></i> <i class="<?= $_style['icon_bars'] ?>" aria-hidden="true"></i> Menu links:hover </h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_menuHColor')" class="pull-right resetcolor btn btn-secondary"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
        </div>
        <div class="panel-body collapse menuHColors">
        <div class="evocp-menuHColors">
        <div class="evocp-menuHColor">#000</div>
        <div class="evocp-menuHColor">#222</div>
        <div class="evocp-menuHColor">#555</div>
        <div class="evocp-menuHColor">#666</div>
        <div class="evocp-menuHColor evocp_light">#dedede</div>
        <div class="evocp-menuHColor evocp_light">#fafafa</div>
        <div class="evocp-menuHColor evocp_light">#fff</div>
        <div class="evocp-menuHColor">#b45f06</div>
        <div class="evocp-menuHColor">#38761d</div>
        <div class="evocp-menuHColor">#134f5c</div>
        <div class="evocp-menuHColor">#0b5394</div>
        <div class="evocp-menuHColor">#351c75</div>
        <div class="evocp-menuHColor">#741b47</div>
        <div class="evocp-menuHColor">#9d2661</div>
        </div>
      <input class="color" type="color" id="menuHcolorPicker" name="evocpCustommenuHColor" value="#cf2626" placeholder="color code...">
        </div>
        </div>
        <div class="panel panel-default">
        <div class="panel-heading">
        <h3 data-toggle="collapse" data-target=".cpcolors"><i class="togglearrow <?= $_style['icon_chevron_right'] ?>" aria-hidden="true"></i> <i class="<?= $_style['icon_font'] ?>" aria-hidden="true"></i> Text color </h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_color')" class="pull-right resetcolor btn btn-secondary"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
        </div>
        <div class="panel-body collapse cpcolors">
        <div class="evocp-colors">
        <div class="evocp-color">#000</div>
        <div class="evocp-color">#222</div>
        <div class="evocp-color">#333</div>
        <div class="evocp-color">#444</div>
        <div class="evocp-color">#555</div>
        <div class="evocp-color">#777</div>
        <div class="evocp-color">#888</div>
        <div class="evocp-color">#b45f06</div>
        <div class="evocp-color">#38761d</div>
        <div class="evocp-color">#134f5c</div>
        <div class="evocp-color">#0b5394</div>
        <div class="evocp-color">#351c75</div>
        <div class="evocp-color">#741b47</div>
        <div class="evocp-color">#9d2661</div>
        </div>
      <input class="color" type="color" id="textcolorPicker" name="textcolorPicker" value="#cf2626" placeholder="color code...">
        </div>
        </div>
        <div class="panel panel-default">
        <div class="panel-heading">
            <h3 data-toggle="collapse" data-target=".alinkcolors"><i class="togglearrow <?= $_style['icon_chevron_right'] ?>" aria-hidden="true"></i> <i class="<?= $_style['icon_chain'] ?>" aria-hidden="true"></i> Links Color</h3> <a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_alinkcolor')" class="pull-right resetcolor btn btn-secondary"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
        </div>
        <div class="panel-body collapse alinkcolors">
        <div class="evocp-alinkcolors">
        <div class="evocp-alinkcolor">#000</div>
        <div class="evocp-alinkcolor">#222</div>
        <div class="evocp-alinkcolor">#555</div>
        <div class="evocp-alinkcolor">#666</div>
        <div class="evocp-alinkcolor">#dedede</div>
        <div class="evocp-alinkcolor">#fafafa</div>
        <div class="evocp-alinkcolor">#fff</div>
        <div class="evocp-alinkcolor">#b45f06</div>
        <div class="evocp-alinkcolor">#38761d</div>
        <div class="evocp-alinkcolor">#134f5c</div>
        <div class="evocp-alinkcolor">#0b5394</div>
        <div class="evocp-alinkcolor">#351c75</div>
        <div class="evocp-alinkcolor">#741b47</div>
        <div class="evocp-alinkcolor">#9d2661</div>
        </div>
      <input class="color" type="color" id="linkcolorPicker" name="alinkcolorPicker" value="#cf2626" placeholder="color code...">
        </div>
        </div>
 <hr/>
        <input type="reset" onclick="cleanLocalStorageReloadAll('my_evo_alinkcolor,my_evo_menuColor,my_evo_menuHColor,my_evo_bgmcolor,my_evo_color')" class="btn btn-secondary" value="<?= $_lang['reset'] ?>">
    </div>
  </div>
<script>
$("#bgmPicker").spectrum({
  showButtons: false,
  preferredFormat: "hex3",
  containerClassName: 'bgmPicker',
    showInput: true,
    allowEmpty:true
});
$("#menucolorPicker").spectrum({
  showButtons: false,
  preferredFormat: "hex3",
  containerClassName: 'menucolorPicker',
  replacerClassName: 'evo-cp-replacer',
    showInput: true,
    allowEmpty:true
});
$("#menuHcolorPicker").spectrum({
    showButtons: false,
  preferredFormat: "hex3",
  containerClassName: 'menuHcolorPicker',
  replacerClassName: 'evo-cp-replacer',
    showInput: true,
    allowEmpty:true
});
$("#textcolorPicker").spectrum({
    showButtons: false,
  preferredFormat: "hex3",
  containerClassName: 'textcolorPicker',
  replacerClassName: 'evo-cp-replacer',
    showInput: true,
    allowEmpty:true
});
$("#linkcolorPicker").spectrum({
    showButtons: false,
  preferredFormat: "hex3",
  containerClassName: 'linkcolorPicker',
  replacerClassName: 'evo-cp-replacer',
    showInput: true,
    allowEmpty:true
});
</script>
