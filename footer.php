<?php
$footer = '
</div><!-- Close Body Content -->
<div><hr></div><!-- Demarcation for bottom of page contents / start of page footer -->
<div class="container" style="text-align:right"><!-- Copyleft, etc -->
	<em>OnCall v'.$ONCALL_VERSION.', (ɔ) 2025 jacripe, Copyleft- some rights reversed</em>
</div>

<script>
function search() {
	var term = document.getElementById("search").value;
	console.log(`SEARCH: ${term}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `q=${term}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(`RESPONSE: `+response.code+` : `+response.url);
		location.href = response.url;
	});
}
</script>
</main>
</body>
</html>';

print($footer);
?>
