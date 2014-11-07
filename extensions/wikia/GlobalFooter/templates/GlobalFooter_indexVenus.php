<footer class="global-footer vertical-<?= $vertical_short ?> row">
	<nav class="mw-content-text">
		<svg class="footer-wikia-logo" version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 217.8 60" enable-background="new 0 0 217.8 60" xml:space="preserve">
			<path fill="blue" d="M100.6,58.8V0h13.2v33l3.5-4.4l7.4-8.8h18.9L128,35.2l16.5,23.7h-17.2l-9-14.9l-4.6,4.3v10.5H100.6z M51.8,20.1l-5,26.4l-6.4-26.4h-6h-0.3h-2.7h-0.3h-6l-6.4,26.4l-5-26.4H0l10.1,38.8h17.7l5-20.4l5,20.4h17.7l10.1-38.8H51.8z M217.1,47.5l0.7,11.3h-12.1l-0.9-4.2c-2.8,2.9-6.2,5.4-12.3,5.4c-11,0-17-7.1-17-20.6c0-13.5,6-20.6,17-20.6c6.1,0,9.5,2.4,12.3,5.4l0.9-4.2h12.1l-0.7,11.3V47.5z M203.9,34.4c-1.7-2.2-4.3-3.7-7.8-3.7c-4,0-7.1,2.6-7.1,8.7c0,6.1,3.2,8.7,7.1,8.7c3.5,0,6.1-1.5,7.8-3.7V34.4zM79.8,0.2c-4.2,0-7.6,3.4-7.6,7.6c0,4.2,3.4,7.6,7.6,7.6c4.2,0,7.6-3.4,7.6-7.6C87.4,3.6,84,0.2,79.8,0.2 M91.2,27.8v-8.3h-5.7H72.2v13.4v12.5v13.1v0.3h19v-8.2h-5.9V27.8H91.2z M153.7,7.8c0,4.2,3.4,7.6,7.6,7.6c4.2,0,7.6-3.4,7.6-7.6c0-4.2-3.4-7.6-7.6-7.6C157.1,0.2,153.7,3.6,153.7,7.8 M155.8,27.8v22.8h-5.9v8.2h19v-0.3V45.4V32.9V19.5h-13.2h-5.7v8.3H155.8z"/>
		</svg>
		<ul class="footer-links">
			<?php
			foreach ($footer_links as $link) {
				?>
				<li>
					<?php
					if ($link['isLicense']) {
						echo $copyright;
					} else {?>
						<a<?= ( !empty( $link[ 'id' ] ) ) ? " id=\"{$link[ 'id' ]}\"" : null ;?> href="<?= $link["href"]; ?>"<?= ( !empty( $link[ 'nofollow' ] ) ) ? ' rel="nofollow"' : null ;?>><?= $link["text"]; ?></a>
					<?php } ?>
				</li>
			<?php
			}
			?>
		</ul>
	</nav>
</footer>
