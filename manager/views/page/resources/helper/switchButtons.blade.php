<form id="switchForm_{{ $id }}" class="form-group switchForm" data-target="{{ $id }}_content" style="display:none">
    <div class="form-row">
        <label class="form-check">
            <input type="radio" name="view" value="list" />
            {{ ManagerTheme::getLexicon('viewopts_radio_list') }}
        </label>
        <label class="form-check">
            <input type="radio" name="view" value="inline" />
            {{ ManagerTheme::getLexicon('viewopts_radio_inline') }}
        </label>
        <label class="form-check">
            <input type="radio" name="view" value="flex" />
            {{ ManagerTheme::getLexicon('viewopts_radio_flex') }}
        </label>
        <input type="number" placeholder="Columns" name="columns" class="form-control form-control-sm columns" value="3" size="3" />
    </div>
    <div class="form-row">
        <label class="form-check">
            <input type="checkbox" name="cb_buttons" value="buttons" />
            {{ ManagerTheme::getLexicon('viewopts_cb_buttons') }}
        </label>
        <label class="form-check">
            <input type="checkbox" name="cb_description" value="description" />
            {{ ManagerTheme::getLexicon('viewopts_cb_descriptions') }}
        </label>
        <label class="form-check">
            <input type="checkbox" name="cb_icons" value="icons" />
            {{ ManagerTheme::getLexicon('viewopts_cb_icons') }}
        </label>
    </div>
    <div class="form-row">
        <label>
            {{ ManagerTheme::getLexicon('viewopts_fontsize') }}
            <input type="number" placeholder="" name="fontsize" class="form-control form-control-sm fontsize" value="10" />
        </label>
    </div>
    <div class="form-row">
        <label>
            <input type="checkbox" class="cb_all" name="cb_all" value="all" />
            {{ ManagerTheme::getLexicon('viewopts_cb_alltabs') }}
        </label>
    </div>
    <div class="optionsLeft optionsReset">
        <a href="javascript:;" class="btn btn-danger btn-sm btn_reset">{{ ManagerTheme::getLexicon('reset') }}</a>
    </div>
</form>
