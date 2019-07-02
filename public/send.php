<?php
$ips = json_decode(file_get_contents(__DIR__ . '/../config/send-ips.json'), true);

if (is_null($ips)) {
	http_response_code(503);
	exit;
}

if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
	header('X-Detected-IP: ' . $_SERVER['REMOTE_ADDR']);
	http_response_code(403);
	exit;
}

require __DIR__ . '/../config/settings.php';
?>
<html lang="en">
	<head>
		<title>Telegram debug</title>
	</head>
	<body>
		<form method="POST" action="hook.php?key=<?php echo TELEGRAM_API_KEY; ?>">
            <div>
                <label for="json">JSON update</label>
            </div>
			<div>
                <textarea rows="10" cols="80" id="json"></textarea>
            </div>
			<div>
				<input type="submit" value="Send"/>
			</div>
		</form>
		<div>
			<pre id="result"></pre>
		</div>
		<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.js"></script>
		<script type="text/javascript">
			jQuery(function($) {
				var $form = $('form');
				var $result = $('#result');

				$form.on('submit', function(e) {
					e.preventDefault();

					var resultText = '';

					$result.text('Loading...');

					$.ajax({
						url: $form.prop('action'),
						method: $form.prop('method'),
						data: $form.find('textarea').val(),
						contentType: 'application/json'
					}).always(function(arg0, textStatus, arg2) {
						resultText = 'Status: ' + textStatus + '\n';
					}).done(function(data, textStatus, jqXHR) {
						resultText += 'Content length: ' + data.length + '\n\n' + data;
					}).fail(function(jqXHR, textStatus, errorThrown) {
						resultText += '\n' + errorThrown;
					}).always(function() {
						$result.text(resultText);
					});
				});
			});
		</script>
	</body>
</html>
