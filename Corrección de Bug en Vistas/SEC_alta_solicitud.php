<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/listpermisos.php';
?>

<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/breadcrumbs.css'); ?>">

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div id="cabecera">
     <?=$titulo?> - Nueva Solicitud
     <span class="pull-right">
       <a href="<?= base_url(); ?>" class="fa fa-home"> Inicio</a> /
       <a href="<?php echo base_url(); ?><?=strtolower($titulo)?>/solicitudes"><?=$titulo?> Solicitudes</a> /
       <span class="text-muted"><?=$subtitulo?></span>
     </span>
    </div>

    <section class="content">
      <div class="row">
          <div class="col-md-12">
              <?php
              $this->load->helper('form');
              if ($this->session->flashdata('error')): ?>
                  <div class="alert alert-danger alert-dismissable">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                      <?= $this->session->flashdata('error'); ?>
                  </div>
              <?php endif; ?>
              <?php if ($this->session->flashdata('success')): ?>
                  <div class="alert alert-success alert-dismissable">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                      <?= $this->session->flashdata('success'); ?>
                  </div>
              <?php endif; ?>
          </div>
      </div>

      <div class="row">
          <div class="col-xs-8">
            	<div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Detalles</h3>
                </div>
            		<div class="box-body">
            			<form action="<?=base_url('ordenes/altanuevasolicitud')?>" method="post" >
            			<input name="sector" type="hidden" value="<?=$sector?>">
                  <p class="text-danger"><h4>Los equipos que figuren en rojo se encuentran con orden de reparacion abierta.</h4></p>
            			<div class="row">
            				<div class="col-md-6">
            					<div class="form-group">
            						<label for="equipo">Equipo</label>
            							<select id="equipo" name="equipo[]" class="form-control selectpicker" multiple data-live-search="true" title="Seleccione un equipo..." data-size="10" data-max-options="1" required>
                          <?php foreach ($equipos as $equipo):?>
                          <option value="<?=$equipo->id?>" <?=$this->ordenes_model->getIdsOrdenesAbiertas($equipo->serie,$sector) && $sector == 'R'?'style="color: red;" disabled ':''?>><?="$equipo->serie - $equipo->estado_descrip"?></option>
                        <?php endforeach;?>
            							</select>
            					</div>
            				</div>
            				<div class="col-md-6">
            					<div class="form-group">
            						<label for="problema">Tipo de problema</label>
            							<select id="problema" name="problema[]" class="form-control selectpicker" multiple data-live-search="true" title="Seleccione un tipo de problema..." data-size="10" data-max-options="1" required>
            								<option value="0" default>Seleccione un tipo de problema...</option>
            								<?php foreach ($fallas as $falla):?>
            									<option value="<?=$falla->id?>"><?=$falla->descrip?></option>
            								<?php endforeach;?>
            							</select>
            					</div>
            				</div>
            			</div>
            			<?php if ($sector == 'R'):?>
            			<div class="row">
            				<div class="col-md-12">
                    			<div class="form-group">
                            <label for="operativo">¿Continuar bajadas durante la reparación?</label><br>
                            <input id="operativo" name="operativo" type="checkbox" class="form-control" data-toggle="toggle" data-on="Si" data-off="No" data-onstyle="success" data-offstyle="danger">
                				</div>
                			</div>
                		</div>
                		<?php endif;?>
            			<div class="row">
            				<div class="col-md-12">
            					<div class="form-group">
            						<label for="observ">Observación</label>
            							<textarea id="observ" name="observ" rows="3" class="form-control" style="resize: none;" placeholder="Ingrese una observación..." required></textarea>
            					</div>
            				</div>
            			</div>

            		</div>

                <div class="box-footer">
                    <button class="btn btn-success" type="submit">Guardar</button>
                </div>
              </form>
            	</div>
            </div>

            <div class="col-md-4">
              <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Ordenes Abiertas</h3>
                </div>
                <div class="box-body">
                  <span id="DataResult"></span>
                </div>
              </div>
            </div>

        </div>
    </section>
</div>

<script>
    $(function() {
        $("#equipo").change(function () {
           $("#equipo option:selected").each(function () {
                equipo = $(this).val();
                $.post("<?=base_url('ordenes_abiertas')?>", {idequipo: equipo})
                .done(function(data) {
                  datos = JSON.parse(data);
                  var html = '';
                  var i;
                  for (i = 0; i < datos.length; i++) {
                    html += '<ul><li type="circle"><b>N° Orden: </b>' + datos[i]['id'] + '</li><li type="circle"><b>Tipo: </b>' + datos[i]['tipo'] + '</li><li type="circle"><b>Falla: </b>' + datos[i]['descrip'] + '</li></ul><br>';
                  }
                  $('#DataResult').html(html);
            		});
            });
        });
    });
</script>
