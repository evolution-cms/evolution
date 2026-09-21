<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php $_style = ManagerTheme::getStyle();?>
<head>
    <title>KCFinder: /
        <?php echo $this->session['dir'] ?></title>
    <?php INCLUDE "tpl/tpl_css.php" ?>
    <?php INCLUDE "tpl/tpl_javascript.php" ?>
</head>

<body>
    <script type="text/javascript">
        $('body').noContext();
    </script>
    <div id="resizer"></div>
    <div id="shadow"></div>
    <div id="dialog"></div>
    <div id="alert"></div>
    <div id="clipboard"></div>
    <div id="all">
        <div id="left">
            <div id="folders"></div>
        </div>
        <div id="right">
            <div id="collapseSide"><a href="javascript:;" id="hide-side"><i id="hide-icon" class="<?= $_style['icon_bars'] ?>"></i></a>
            </div>
            <div id="toolbar">
                <div>
                    <a href="kcact:upload">
                        <?php echo $this->label("Upload") ?></a>
                    <a href="kcact:refresh">
                        <?php echo $this->label("Refresh") ?></a>
                    <a href="kcact:settings">
                        <?php echo $this->label("Settings") ?></a>
                    <a href="kcact:maximize">
                        <?php echo $this->label("Maximize") ?></a>

                    <div id="view">
                        <input id="viewThumbs" type="radio" name="view" value="thumbs" />
                        <label class="radio-thumbs" for="viewThumbs"></label>
                        <input id="viewList" type="radio" name="view" value="list" />
                        <label class="radio-list" for="viewList"></label>
                    </div>
                    <div class="rangeThumbContainer">
                        <input id="rangeThumb" class="rangeThumb" type="range" value="150" min="50" max="400" step="10"><span class="thumbsize"></span>
                    </div>
                    <div class="rangeTextContainer">
                        <input id="rangeText" class="rangeText" type="range" value="12" min="12" max="28" step="1"><span class="textsize"></span>
                    </div>
                    <div id="loading"></div>
                </div>
            </div>
            <div id="settings">
                <div>
                    <fieldset>
                        <legend>
                            <?php echo $this->label("Show:") ?></legend>
                        <table summary="show" id="show">
                            <tr>
                                <th>
                                    <input id="showName" type="checkbox" name="name" />
                                </th>
                                <td>
                                    <label for="showName">&nbsp;
                                        <?php echo $this->label("Name") ?></label> &nbsp;</td>
                                <th>
                                    <input id="showSize" type="checkbox" name="size" />
                                </th>
                                <td>
                                    <label for="showSize">&nbsp;
                                        <?php echo $this->label("Size") ?></label> &nbsp;</td>
                                <th>
                                    <input id="showTime" type="checkbox" name="time" />
                                </th>
                                <td>
                                    <label for="showTime">&nbsp;
                                        <?php echo $this->label("Date") ?></label>
                                </td>
                            </tr>
                        </table>

                    </fieldset>
                </div>

                <div>
                    <fieldset>
                        <legend>
                            <?php echo $this->label("Order by:") ?></legend>
                        <table summary="order" id="order">
                            <tr>
                                <th>
                                    <input id="sortName" type="radio" name="sort" value="name" />
                                </th>
                                <td>
                                    <label for="sortName">&nbsp;
                                        <?php echo $this->label("Name") ?></label> &nbsp;</td>
                                <th>
                                    <input id="sortType" type="radio" name="sort" value="type" />
                                </th>
                                <td>
                                    <label for="sortType">&nbsp;
                                        <?php echo $this->label("Type") ?></label> &nbsp;</td>
                                <th>
                                    <input id="sortSize" type="radio" name="sort" value="size" />
                                </th>
                                <td>
                                    <label for="sortSize">&nbsp;
                                        <?php echo $this->label("Size") ?></label> &nbsp;</td>
                                <th>
                                    <input id="sortTime" type="radio" name="sort" value="date" />
                                </th>
                                <td>
                                    <label for="sortTime">&nbsp;
                                        <?php echo $this->label("Date") ?></label> &nbsp;</td>
                                <th>
                                    <input id="sortOrder" type="checkbox" name="desc" />
                                </th>
                                <td>
                                    <label for="sortOrder">&nbsp;
                                        <?php echo $this->label("Descending") ?></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </div>

            </div>
            <div id="files">
                <div id="content"></div>
            </div>
        </div>
        <div id="status"><span id="fileinfo">&nbsp;</span>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            if ($("#viewThumbs").is(":checked")) {
                $('label.radio-thumbs').addClass('labelchecked');
                $('.rangeTextContainer').addClass('hiddenrange');
                $('.rangeThumbContainer').removeClass('hiddenrange');
            } else {
                $('label.radio-list').addClass('labelchecked');
                $('.rangeThumbContainer').addClass('hiddenrange');
                $('.rangeTextContainer').removeClass('hiddenrange');
            }
        });
        $('.rangeThumb').change(function() {
            var v = $(this).val();
            $('div.thumb').css('height', v + 'px')
            $('div.thumb').css('width', v + 'px')
            $('div.thumb img').css('height', v + 'px')
            $('div.thumb img').css('width', v + 'px')
            $('div.file').css('width', v + 'px')
            $('.thumbsize').html(v + 'px');
        });
        $('.rangeText').change(function() {
            var v = $(this).val();
            $('tr.file td').css('font-size', v + 'px')
            $('.textsize').html(v + 'px');
        });
        $("#hide-side").click(function() {
            var x = document.getElementById('left');
            if (x.style.display === 'none') {
                x.style.display = 'block';
                $('#hide-icon').toggleClass('fa-bars fa-ellipsis-v');
                $('#right').css("width", "74%");
            } else {
                x.style.display = 'none';
                $('#hide-icon').toggleClass('fa-bars fa-ellipsis-v');
                $('#right').css("width", "99%");
                $('#files').css("width", "99%");
            }

        });
        $("label.radio-list").click(function() {
            $('label.radio-list').addClass('labelchecked');
            $('label.radio-thumbs').removeClass('labelchecked');
            $('.rangeThumbContainer').addClass('hiddenrange');
            $('.rangeTextContainer').removeClass('hiddenrange');
        });
        $("label.radio-thumbs").click(function() {
            $('label.radio-thumbs').addClass('labelchecked');
            $('label.radio-list').removeClass('labelchecked');
            $('.rangeTextContainer').addClass('hiddenrange');
            $('.rangeThumbContainer').removeClass('hiddenrange');
        });
    </script>
</body>

</html>