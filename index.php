<?php
session_start();
if (!isset($_SESSION['token'])) {
    $token = md5(uniqid(rand(), TRUE));
    $_SESSION['token'] = $token;
}
else
{
    $token = $_SESSION['token'];
}

$message = '';
$status = false;

if (!empty($_POST) && !hash_equals($token, $_POST['token'])) {
    throw new Exception('CSRF token not valid');
}

if (!empty($_POST['domain_name']) &&
    preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/', $_POST['domain_name']) &&
    filter_var($_POST['domain_name'], FILTER_SANITIZE_URL)
    // Эта проверка необходимо для импользования на бою, для тестирования можно закомментировать
//    && checkdnsrr($_POST['domain_name'])
) {
    try {
        $domain = ltrim(filter_var($_POST['domain_name'], FILTER_SANITIZE_URL),'www.');

        if (in_array($domain, scandir('./nginx/'))) {
            throw new Exception("Для домена {$domain} уже добавлен ssl сертефикат");
        }

        if (!empty($_FILES['ssl_file'])) {
            $pem_file = file_get_contents($_FILES['ssl_file']['tmp_name']);
            if($pub_key = openssl_x509_read($pem_file)) {
                $key_info = openssl_x509_parse($pem_file);
                // Этот необходимо для импользования на бою, для тестирования самоподписанных можно закомментировать
//                if(!openssl_x509_checkpurpose($pub_key, X509_PURPOSE_SSL_SERVER)){
//                    throw  new Exception('Полученный сертефикат не является доверительным, пожалуйста, попробуйте другой');
//                }
                if($key_info['validTo_time_t'] < (time() + 3600*12*10)){
                    throw  new Exception("Полученный сертефикат истекает в течении 10 дней или уже истек.\r\n Предлагаем вам получить новый.");
                }

                if(!is_writable('./ssl/')) {
                    throw new Exception('ssl dir is\'t writable');
                }
                file_put_contents("./ssl/{$domain}.pem", $pem_file);
                $config = str_replace('%domain%', $domain, file_get_contents('./nginx_config.tpl'));
                if(!is_writable('./nginx/')){
                    throw new Exception('ssl dir is\'t writable');
                }
                file_put_contents("./nginx/{$domain}", $config);
                $message = 'Сертефикат успешно добавлен. Ожидайте его применение в течении 5 минут';
                $status = true;
            } else {
                throw new Exception('Выбранный вами файл не является ssl-сертефикатом');
            };

        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
} else {
    $message = 'Введено некоретное доменное имя';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>title</title>
    <meta name="author" content="name">
    <meta name="description" content="description here">
    <meta name="keywords" content="keywords,here">
    <link rel="stylesheet" href="css/styles.css" type="text/css">
</head>
<body>
<div class="flex-container">
    <div class="row">
        <div class="flex-item">
            <h3 class="<?= $status ? 'success' : 'error'?>"><?=$message?></h3>
            <form id="add_ssl" name="add_ssl" method="POST" action="" enctype="multipart/form-data">
                <input id="token" name="token" type="hidden" value="<?=$token?>">
                <div class="form-row">
                    <label for="domain_name">Имя домена http://</label>
                    <input id="domain_name" name="domain_name"
                           type="text"
                           placeholder="mydomain.com"
                           pattern="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$"
                           required autofocus maxlength="100"> /
                </div>


                <div class="form-row"><label for="ssl_file">Файл сертефиката *.cert или *.pem</label>
                    <input id="ssl_file" name="ssl_file" required
                           type="file" accept="application/x-x509-ca-cert">
                </div>
                <input type="submit" value="Отправить">
            </form>
        </div>
    </div>
</div>
</body>
</html>