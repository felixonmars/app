<ul id="AccountNavigation" class="AccountNavigation table-cell">
	<li class="account-navigation-first-item">
		<div class="links-container">
			<?= $navItemLinkOpeningTag ?>
				<div class="bubbles">
					<div class="bubbles-count notifications-count"></div>
				</div>
				<div class="avatar-container<?= $avatarContainerAdditionalClass ?>">
					<?= $profileAvatar ?>
				</div>
			</a>
			<?php if ($isAnon) : ?>
				<span class="sign-in-label"><?= wfMessage( 'global-navigation-sign-in' )->escaped(); ?></span>
			<? endif; ?>
			<img class="chevron" src="data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw%3D%3D"/>
		</div>
		<?php if ( !$isAnon ): ?>
		<ul class="user-menu subnav">
			<?= $app->renderView( 'GlobalNavigationWallNotifications', 'Index' ); ?>
			<?php foreach( $userDropdown as $link ): ?>
				<li><?= $link ?></li>
			<?php endforeach; ?>
		</ul>
		<?php else:
			echo $loginDropdown;
		endif; ?>
	</li>
</ul>
