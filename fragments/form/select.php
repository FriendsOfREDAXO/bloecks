<?php
$options = $this->getVar('options');
$group = $this->getVar('group');
$size = $this->getVar('size');
$name = $this->getVar('name');
$label = $this->getVar('label');

if(($min = $this->getVar('min')))
  $size = min($min,$size);

?><dl class="rex-form-group form-group<?php echo ($group?' group_'.$group:'');?>">
  <dt>
    <label class="control-label" for="<?php echo rex_string::normalize($name,'');?>"><?php echo $label;?></label>
  </dt>
  <dd>
    <?php
      $newSelect = new rex_select();
      $newSelect->setMultiple($this->getVar('multiple'));
      $newSelect->setAttribute('class','form-control');
      $newSelect->setId(rex_string::normalize($name,''));
      $newSelect->setSize($size);
      $newSelect->setName($name);
      $newSelect->setSelected($this->getVar('selected'));

      if (count($options) > 0) {
        foreach ($options as $key => $module) {
          if(is_array($module))
            $newSelect->addOption($module['name'],$module['id']);
          else $newSelect->addOption($module,$key);
        }
      }
      echo $newSelect->get();
    ?>
    <br><span class="rex-form-notice"><?php echo $this->getVar('info');?></span>
  </dd>
</dl>