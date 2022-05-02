<?php
$this->load->view('admin/layouts/header');
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Products
<!--            <small>Listing</small>-->
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Products</li>
        </ol>
    </section>

    <section class="content">
        <!-- Main row -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <?= form_open_multipart('admin/products') ?>
                    <div class="box-header">
                        <h3 class="box-title">Add Product</h3>
                        <input type="hidden" name="pid" id="pid" />
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Title</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="title" id="title" class="form-control" value="<?= set_value('title') ?>" />
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Description</label>
                                    </div>
                                    <div class="col-md-6">
                                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Price</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="price" id="price" class="form-control" value="<?= set_value('price') ?>" />
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Nationality</label>
                                    </div>
                                    <div class="col-md-6">
                                        <?= form_dropdown('nationality_id', $nationality, '0', ['class' => 'form-control', 'id' => 'nationality']) ?>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Location</label>
                                    </div>
                                    <div class="col-md-6">
                                        <?= form_dropdown('location_id', $location, '0', ['class' => 'form-control', 'id' => 'location']) ?>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Whatsapp</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="whatsapp" id="whatsapp" class="form-control" value="<?= !empty($callaction->whatsapp) ? $callaction->whatsapp : set_value('whatsapp') ?>" />
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3">
                                        <label>Call Now</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="callnow" id="callnow" class="form-control" value="<?= !empty($callaction->callnow) ? $callaction->callnow : set_value('callnow') ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row form-group">
                                    <div class="col-md-4">
                                        <!--<label>Product Images</label>-->
                                        <div class="col-md-10 thumbnail">
                                            <img src="/assets/upload/default.png" id="image" style="width: 150px;height: 150px;">
                                            <input type="file" name="image" onchange="readURL0(this)" class="form-control">
                                            <!--<input type="hidden" name="hidden_image1" class="form-control" value="">-->
                                        </div>
                                    </div>
                                    <div class="col-md-3 thumbnail">
                                        <img src="/assets/upload/default.png" id="image1" style="width: 150px;height: 150px;">
                                        <input type="file" name="image1" onchange="readURL(this)" class="form-control">
                                        <!--<input type="hidden" name="hidden_image1" class="form-control" value="">-->
                                    </div>
                                    <div class="col-md-3 col-md-offset-1 thumbnail">
                                        <img src="/assets/upload/default.png" id="image2" style="width: 150px;height: 150px;">
                                        <input type="file" name="image2" onchange="readURL1(this)" class="form-control">
                                        <!--<input type="hidden" name="hidden_image2" class="form-control" value="">-->
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-3 col-md-offset-4 thumbnail">
                                        <img src="/assets/upload/default.png" id="image3" style="width: 150px;height: 150px;">
                                        <input type="file" name="image3"  onchange="readURL2(this)" class="form-control">
                                        <!--<input type="hidden" name="hidden_image3" class="form-control" value="">-->
                                    </div>
                                    <div class="col-md-3 col-md-offset-1 thumbnail">
                                        <img src="/assets/upload/default.png" id="image4" style="width: 150px;height: 150px;">
                                        <input type="file" name="image4" onchange="readURL3(this)" class="form-control">
                                        <!--<input type="hidden" name="hidden_image4" class="form-control" value="">-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="submit" name="btnSub" id="btnSub" class="btn btn-primary" value="Save" />
                                <input type="reset" name="btnCan" id="btnCan" class="btn btn-default" value="Cancel" />
                            </div>
                        </div>
                    </div>
                    <?= form_close() ?>
                </div>
            </div>
            <!-- /.box -->
        </div>
        <!-- /.row (main row) -->
    </section>
    <!-- Main content -->
    <section class="content">
        <!-- Main row -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Products List</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table id="example2" class="table table-bordered table-hover">
                            <colgroup>
                                <col width="5%"></col>
                                <col width="20%"></col>
                                <col width="30%"></col>
                                <col width="10%"></col>
                                <col width="10%"></col>
                                <col width="10%"></col>
                                <col width="15%"></col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Nationality</th>
                                    <th>Location</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($products)) {
                                    $i = 1;
                                    foreach ($products as $key) {
                                        $id = $key->id;
                                        ?>
                                        <tr>
                                            <td><?= $i ?></td>
                                            <td><?= ucfirst($key->title); ?> </td>
                                            <td><?= $key->description ?></td>
                                            <td><?= $key->price ?></td>
                                            <td><?= $key->nationality ?></td>
                                            <td><?= $key->location ?></td>
                                            <td>
                                                <a class="btn btn-primary" onclick="editProduct(<?= $id ?>)" id="edit-<?= $id ?>"><i class="fa fa-edit"></i></a>
                                                <a class="btn btn-danger" onclick="deleteProduct(<?= $id ?>)"  id="del-<?= $id ?>"><i class="fa fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php
                                        $i++;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
            <!-- /.box -->
        </div>
        <!-- /.row (main row) -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
$this->load->view('admin/layouts/footer');
?>