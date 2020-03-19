<?php
function title_sort($a, $b) {
	return strcmp($a['title'], $b['title']);
}
$forms = GFAPI::get_forms();
usort($forms, "title_sort");
$skip_forms = get_blue_corona_rrp_skip_forms();
?>
<h1>Blue Corona RRP Settings</h1>
<?php settings_errors();?>
<script>
// Show an element
var show = function(elem) {
    elem.style.display = 'block';
};

// Hide an element
var hide = function(elem) {
    elem.style.display = 'none';
};

// Toggle element visibility
var toggle = function(elem) {

    // If the element is visible, hide it
    if (window.getComputedStyle(elem).display === 'block') {
        hide(elem);
        return;
    }

    // Otherwise, show it
    show(elem);

};

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('dev_mode').addEventListener('click', function(event) {
        if (event.target.checked) {
            show(document.getElementById('api_url_row'));
        } else {
            hide(document.getElementById('api_url_row'));
        }
    }, false);
});
</script>
<form method="post">
	<table class="form-table">
	    <tbody>
			<tr>
			    <th scope="row">
			        <label for="api_key">API Key</label>
			    </th>

			    <td>
			        <input type="text" value="<?php echo htmlentities($api_key); ?>" id="api_key" name="api_key" style="width:400px;">
			        <br>
			        <span class="description"></span>
			    </td>
			</tr>
			<tr>
			    <th scope="row">
			        <label for="skip_forms">Skip Forms</label>
			    </th>
			    <td>
			    	<ul class="checkbox">
			    	<?php foreach ($forms as $form): ?>
			    		<li><label><?=htmlentities($form['title'])?>&nbsp;<input type="checkbox" name="skip_forms[]" value="<?=htmlentities($form['id'])?>" <?php if (in_array($form['id'], $skip_forms)) {
	echo "checked";
}
?>></label></li>
			    	<?php endforeach;?>
			    	</ul>
			        <br>
			        <span class="description"></span>
			    </td>
			</tr>
			<tr>
			    <th scope="row">
			        <label for="dev_mode">Development Mode</label>
			    </th>

			    <td>
			        <input type="checkbox" <?php if (!empty($dev_mode)) {
	echo "checked";
}
?> id="dev_mode" name="dev_mode">
			        <br>
			        <span class="description"></span>
			    </td>
			</tr>

			<tr id="api_url_row" style="display: <?php if (!empty($dev_mode)) {echo "block";} else {echo "none";}?>">
			    <th scope="row">
			        <label for="api_url">API URL</label>
			    </th>

			    <td>
			        <input type="text" value="<?php echo htmlentities($api_url); ?>" id="api_url" name="api_url" style="width:400px;">
			        <br>
			        <span class="description"></span>
			    </td>
			</tr>

			<?php if (!empty($_GET['domains'])) {?>
			<tr>
				<td>
					Domains:
					<ul>
						<?php foreach (rrp_domains() as $domain) {?>
							<li style="width:400px;"><?=htmlentities($domain)?></li>
						<?php }?>
					</ul>
				</td>
			</tr>
			<?php }?>

	    </tbody>
	</table>
	<?php
submit_button();
?>
</form>
