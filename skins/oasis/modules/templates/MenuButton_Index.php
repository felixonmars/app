<?php
	if (is_array($action) && !empty($action)) {
		if (empty($dropdown)) {
			// render simple edit button
			if (isset($action['accesskey'])) {
				$accesskey = ($action['accesskey'] !== false) ? (' accesskey="' . $action['accesskey'] . '"') : '';
			}
			else {
				$accesskey = ' accesskey="e"';
			}
?>
<a<?= $accesskey ?> href="<?= htmlspecialchars($action['href']) ?>" class="<?= $class ?>" data-id="<?= $actionName ?>"><?= $icon ?> <?= htmlspecialchars($action['text']) ?><?= $action['html'] ?></a>
<?php
		}
		// render edit button with dropdown
		else {
?>
<nav class="<?= $class ?>" <?= empty($id) ? '' : 'id="'.$id.'"'?>>
<?php
			// render edit menu
			if (isset($action['href'])) {
?>
	<a <?= !empty($actionAccessKey) ? "accesskey=\"{$actionAccessKey}\"" : '' ?> href="<?= empty($action['href']) ? '' : htmlspecialchars($action['href']) ?>" data-id="<?= $actionName ?>" <?= empty($action['id']) ? '' : 'id="'.$action['id'].'"'?>>
		<?= $icon ?> <?= htmlspecialchars($action['text']) ?>
	</a>
<?php
			}
			// render menu without URL defined for a button
			else {
?>
	<?= $icon ?> <?= htmlspecialchars($action['text']) ?>
<?php
			}
?>

	<span class="drop">
		<img src="<?= $wgBlankImgUrl ?>" class="chevron">
	</span>
	<ul>
<?php
			foreach($dropdown as $key => $item) {
				// render accesskeys
				if (!empty($item['accesskey'])) {
					$accesskey = ' accesskey="' . $item['accesskey'] . '"';
				}
				else {
					$accesskey = '';
				}

				$href = isset($item['href']) ? htmlspecialchars($item['href']) : '#';
?>
		<li>
			<a href="<?= $href ?>"<?= $accesskey ?> data-id="<?= $key ?>"<?= empty($item['id']) ? '' : ' id="'.$item['id'].'"' ?><?= empty($item['class']) ? '' : ' class="'.$item['class'].'"' ?>><?=htmlspecialchars($item['text']) ?></a>
		</li>
<?php
			}
?>
	</ul>
</nav>
<?php
		}
	}
?>