<div class="trending-tabs">
	<a href="#" class="btn btn-success" data-tab="popular">Trending &#8599;</a>
	<a href="#" class="btn btn-danger" data-tab="unpopular">Biggest Loosers &#8600;</a>
</div>
<canvas id="chart" width="600" height="400"></canvas>
<div class="tab-contents" data-tab="popular">
	<h2>Trending</h2>
	<table class="table active table-striped table-hover">
		<thead>
			<tr>
				<td>Rank</td>
				<td>Title</td>
				<td>Pageviews change</td>
			</tr>
		</thead>
		<tbody>
			<? for($i = 0; $i < count($trendingArticles); $i++): ?>
			<tr>
				<td><?=$i + 1?>.</td>
				<td><a href="<?= $trendingArticles[$i]['url'] ?> "><?= $trendingArticles[$i]['title'] ?></a></td>
				<td><?= $trendingArticles[$i]['pvDiff'] ?></td>
			</tr>
			<? endfor ?>
		</tbody>
	</table>
</div>

<div class="tab-contents" data-tab="unpopular">
	<h2>Biggest Loosers</h2>
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<td>Rank</td>
			<td>Title</td>
			<td>Pageviews change</td>
			<td>Pageviews</td>
		</tr>
		</thead>
		<tbody>
		<? for($i=0; $i < count($loosingArticles); $i++): ?>
			<tr>
				<td><?=$i+1?>.</td>
				<td><a href="<?=$loosingArticles[$i]['url'] ?>"><?=$loosingArticles[$i]['title'] ?></a></td>
				<td><?=$loosingArticles[$i]['pvDiff'] ?></td>
			</tr>
		<? endfor ?>
		</tbody>
	</table>
</div>
