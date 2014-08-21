<nav class="hubs-menu">
	<div class="hubs">
		<? foreach($menuNodes as $hub): ?>
			<nav class="<?=$hub['specialAttr']; ?>">
				<span class="icon"></span>
				<span class="label"><?=$hub['text']; ?></span>
			</nav>
		<? endforeach ?>
	</div>
	<section>
		<? foreach($menuNodes[0]['children'] as $category): ?>
			<h2><?=$category['text']?></h2>
			<? foreach($category['children'] as $node): ?>
				<a href="<?=$node['href']?>"><?=$node['text']?></a>
			<? endforeach ?>
		<? endforeach ?>
	</section>
</nav>