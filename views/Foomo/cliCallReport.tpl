<?php
/* @var $model Foomo\CliCall */
?>Foomo\CliCall Report :

  called :
  
    <?php echo implode(PHP_EOL . '    ', explode(';', $model->lastCommandExecuted)) ?>
    
<? if(count($model->envVars)>0): ?>  	
  environement variables exported:
 
<?php foreach($model->envVars as $key => $value): ?>
    <?php echo $key . ' => ' . $value ?>
<?php endforeach; ?>
<? endif; ?>

  execution date : 
    
    <?php echo date('Y-m-d H:i:s') ?>
    
  
  exit status:
  
    <?php echo $model->exitStatus ?>

  
<?php foreach(array('stdOut', 'stdErr') as $stdType): ?>
<? if(!empty($model->$stdType)): ?>
<?php echo $stdType ?> :
  
    ---------------------------------------------------------------------------
    | <?php echo implode(PHP_EOL . '    | ', explode(PHP_EOL, $model->$stdType)) ?>

    ---------------------------------------------------------------------------
<? endif; ?>
<?php endforeach; ?>   
