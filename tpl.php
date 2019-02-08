<div class="intkassa" style="display: block">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/interkassa.css">

    <script src="js/main.js"></script>
    <?php if (!empty($error_mes)) { ?>
        <form name="payment_interkassa" action="javascript:selpayIK.selPaysys()" method="POST" class="form_pay">
            <?php echo $hidden_fields; ?>
            <img src="/images/logo_interkassa.png">
            <div class="btn_pay">
                <?php echo $error_mes; ?>
                <div><?php echo $cancel_url; ?></div>
            </div>
        </form>
    <?php } else { ?>
        <form name="payment_interkassa" id="InterkassaForm" action="javascript:selpayIK.selPaysys()" method="POST"
              class="form_pay" style="display: none">
            <?php echo $hidden_fields; ?>
            <img src="/images/logo_interkassa.png">
            <div class="btn_pay">
                <input type="submit" value="Оплатить" class="co-button co-button--small">
                <?php echo $cancel_url; ?>
            </div>
        </form>
        <div class="interkasssa" style="text-align: center;">
            <?php
            if ($this->enabledAPI == 'yes') {
                $payment_systems = $this->getIkPaymentSystems();//$this->api_id, $this->api_key, $this->merchant_id
                if (is_array($payment_systems) && !empty($payment_systems)) {
                    ?>
                    <button type="button" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal"
                            data-target="#InterkassaModal" style="display: none;">
                        Select Payment Method
                    </button>
                    <!--                    <div id="InterkassaModal" class="ik-modal fade" role="dialog">-->
                    <!--                        <div class="ik-modal-dialog ik-modal-lg">-->
                    <div class="ik-modal-content" id="plans">
                        <div class="container">
                            <h3>
                                1. Выберите удобный способ оплаты<br>
                                2. Укажите валюту<br>
                                3. Нажмите &laquo;Оплатить&raquo;<br>
                            </h3>
                            <div class="ik-row">
                                <?php foreach ($payment_systems as $ps => $info) { ?>
                                    <div class="col-sm-3 text-center payment_system">
                                        <div class="panel panel-warning panel-pricing">
                                            <div class="panel-heading">
                                                <div class="panel-image">
                                                    <img src="/images/<?php echo $ps; ?>.png"
                                                         alt="<?php echo $info['title']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="radioBtn btn-group">
                                                        <?php foreach ($info['currency'] as $currency => $currencyAlias) { ?>
                                                            <a class="btn btn-primary btn-sm notActive"
                                                               data-toggle="fun"
                                                               data-title="<?php echo $currencyAlias; ?>"><?php echo $currency; ?></a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="panel-footer">
                                                <a class="btn btn-lg btn-block btn-success ik-payment-confirmation"
                                                   data-title="<?php echo $ps; ?>"
                                                   href="#">Оплатить через<br>
                                                    <strong><?php echo $info['title']; ?></strong>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <!--                        </div>-->
                    <!--                    </div>-->
                    <?php
                } else
                    echo $payment_systems;
            }
            ?>
        </div>
    <?php } ?>


</div>