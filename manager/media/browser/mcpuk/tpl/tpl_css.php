<link href="css.php?v=<?php echo (int) @filemtime("css.php") ?>&amp;type=<?php echo $this->type ?><?php echo ($this->cms ? "&amp;cms={$this->cms}" : "" ) ?>" rel="stylesheet" type="text/css" />
<link href="themes/<?php echo $this->config['theme'] ?>/style.css?v=<?php echo (int) @filemtime("themes/{$this->config['theme']}/style.css") ?>" rel="stylesheet" type="text/css" />
