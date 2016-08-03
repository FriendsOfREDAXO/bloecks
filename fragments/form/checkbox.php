<?php
$value = $this->getVar('value');
$classes = $this->getVar('classes');
$label = $this->getVar('label');
$name = $this->getVar('name');
$info = $this->getVar('info');
?><dl class="rex-form-group form-group">
  <dd>
    <div class="checkbox">
      <label class="control-label" for="<?php echo rex_string::normalize($name,'');?>">
        <input type="checkbox" name="<?php echo $this->getVar('name');?>" value="<?php echo ($value?$value:1);?>" id="<?php echo rex_string::normalize($name,'');?>"<?php echo ($this->getVar('checked')?' checked="checked"':'');?>>
        <?php echo $label;?>
        <?php if(!empty($info)) {?><div class="help-block"><?php echo $info;?></div><?php }?>
      </label>
      <?php if(($fields = $this->getVar('toggleFields'))) {?>
      <script type="text/javascript">
        jQuery(function($) {
          $("#<?php echo rex_string::normalize($name,'');?>").click(function() {
            $("<?php echo str_replace('.','.group_',$fields);?>").slideToggle("slow");
          });
          if($("#<?php echo rex_string::normalize($name,'');?>").is(":checked"))
            $("<?php echo str_replace('.','.group_',$fields);?>").hide();
        });
        </script>
      <?php }?>
    </div>
  </dd>
</dl>