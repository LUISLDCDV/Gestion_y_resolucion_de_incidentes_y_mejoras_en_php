<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/listpermisos.php';
$sector = substr($titulo, 0, 1);
?>

<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/breadcrumbs.css'); ?>">

<div class="content-wrapper">
    <div id="cabecera">
     <?=$titulo?> - Nueva Orden
     <span class="pull-right">
       <a href="<?= base_url(); ?>" class="fa fa-home"> Inicio</a> /
       <a href="<?= base_url(); ?><?=strtolower($titulo)?>/ordenes"><?=$titulo?> Ordenes</a> /
       <span class="text-muted">Nueva Orden</span>
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
        <div class="col-md-8">
          <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Detalles</h3>
            </div>

            <form action="<?=base_url('ordenes/altanuevaorden')?>" method="post" >

            <div class="box-body">
              <input name="sector" type="hidden" value="<?=$sector?>">
              <input name="ref" type="hidden" value="<?=$ref?>">
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
                        <?php foreach ($fallas as $falla):?>
                          <option value="<?=$falla->id?>"><?=$falla->descrip?></option>
                        <?php endforeach;?>
                      </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="operativo">¿Continuar bajadas durante la reparación?</label><br>
                    <input id="operativo" name="operativo" type="checkbox" class="form-control" data-toggle="toggle" data-on="Si" data-off="No" data-onstyle="success" data-offstyle="danger">
                  </div>
                </div>
              </div>

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
          <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Aviso</h3>
            </div>

            <div class="box-body">
                <label for="">Bajada de Memoria</label>
                <p>Si hay una orden de Bajada de Memoria activa, sobre el equipo que esta por crear una orden de Reparacion, se cancelara para tomar como prioridad esta Orden.</p>

                <label for="">Equipos</label>
                <p>Los equipos que estan en Deposito no se pueden crear una orden, solicitarlo al sector Deposito.<br>
                El listado de equipos tiene los siguientes datos:
                  <ul>
                    <li>Los equipos que estan en rojo significa que tiene una orden abierta.</li>
                    <li>Al lado del nombre del equipo esta el lugar donde encuentra el mismo.</li>
                  </ul>
                </p>
            </div>
          </div>
        </div>

      </div>
    </section>
</div>
