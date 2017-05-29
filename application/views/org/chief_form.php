<?php $this->load->view('_template/top');?>
<h1 class="page-header">Organization <small>Change Chief</small></h1>
<?php echo form_open($process, 'class="form"'); ?>

  <div class="form-group">
    <label for="dt_begin">Since </label>
    <input type="date" class="form-control" name="dt_begin" id="dt_begin" value="<?php echo date('Y-m-d')?>" >
  </div>

  <div class="form-group">
    <label for="txt_name">Chief Position</label>
    <?php echo form_dropdown('slc_chief',$chiefOpt, $chiefSlc,'id="slc_chief" class="form-control"'); ?>

  </div>
  <?php $this->load->view('general/form_act_elm'); ?>

</form>
<?php $this->load->view('_template/bottom');?>
