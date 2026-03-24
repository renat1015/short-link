<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Сервис коротких ссылок';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-light p-5 mb-4 rounded">
        <h1 class="display-5">Сервис коротких ссылок</h1>
        <p class="lead">Вставьте длинную ссылку и получите короткую + QR-код</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="url" id="url-input" class="form-control form-control-lg" 
                               placeholder="https://example.com/very-long-url">
                        <button class="btn btn-primary btn-lg" type="button" id="submit-btn">
                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                            OK
                        </button>
                    </div>
                    <div id="error-alert" class="alert alert-danger d-none"></div>
                    
                    <div id="result-section" class="mt-4 d-none">
                        <div class="row">
                            <div class="text-center">
                                <h5>Ваша короткая ссылка:</h5>
                                <div class="mb-3">
                                    <a id="short-url" href="#" target="_blank" class="short-link"></a>
                                </div>
                                <div id="qr-container" class="mb-3">
                                    <img id="qr-image" src="" alt="QR Code" class="img-fluid">
                                </div>
                                <p class="text-muted">
                                    <small>Наведите камеру телефона на QR-код, чтобы открыть ссылку</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$createUrl = Url::to(['/site/create']);
$script = <<<JS
    $('#submit-btn').click(function() {
        $('#short-url').attr('href', '').text('');
        $('#qr-image').attr('src', '');
        $('#result-section').addClass('d-none');

        const url = $('#url-input').val();
        if (!url) {
            showError('Введите URL');
            return;
        }
        
        $('#spinner').removeClass('d-none');
        $('#submit-btn').prop('disabled', true);
        $('#error-alert').addClass('d-none');

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var csrfParam = $('meta[name="csrf-param"]').attr('content');

        var data = {
            url: url
        };
        data[csrfParam] = csrfToken;

        $.post('$createUrl', data)
            .done(function(response) {
                if (response.success) {
                    $('#short-url').attr('href', response.short_url).text(response.short_url);
                    $('#qr-image').attr('src', response.qr_code);
                    $('#result-section').removeClass('d-none');
                } else {
                    showError(response.message);
                }
            })
            .fail(function() {
                showError('Произошла ошибка при обработке запроса');
            })
            .always(function() {
                $('#spinner').addClass('d-none');
                $('#submit-btn').prop('disabled', false);
            });
    });
    
    $('#url-input').keypress(function(e) {
        if (e.which == 13) {
            $('#submit-btn').click();
        }
    });
    
    function showError(message) {
        $('#error-alert').text(message).removeClass('d-none');
    }
JS;

$this->registerJs($script);
?>