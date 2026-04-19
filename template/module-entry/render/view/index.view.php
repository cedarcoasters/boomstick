<?='<?=';?>$this->insertStyle('index');?>

<div class="hero-section">
	<div class="blood-drip"></div>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-10 text-center">
				<img src="/images/ash-shotgun.svg" alt="Ash's Boomstick" class="boomstick-img">
				<h1 class="main-title">
					<span class="this-is">This... is my...</span>
					<span class="boomstick-text">BOOMSTICK!</span>
				</h1>
				<p class="tagline">Shop smart. Shop S-Mart.</p>
			</div>
		</div>
	</div>
</div>

<div class="container features-section">
	<div class="row text-center mb-5">
		<div class="col-12">
			<h2 class="section-title">Groovy Features</h2>
		</div>
	</div>
	<div class="row g-4">
		<div class="col-md-4">
			<div class="feature-card">
				<div class="feature-icon">💀</div>
				<h3>Deadite Defense</h3>
				<p>Twelve gauge, double-barreled Remington. S-Mart's top of the line.</p>
			</div>
		</div>
		<div class="col-md-4">
			<div class="feature-card">
				<div class="feature-icon">⛓️</div>
				<h3>Chainsaw Ready</h3>
				<p>When you've got a possessed hand, sometimes you gotta do what you gotta do.</p>
			</div>
		</div>
		<div class="col-md-4">
			<div class="feature-card">
				<div class="feature-icon">📖</div>
				<h3>Necronomicon Tested</h3>
				<p>Klaatu... Barada... N*cough*... Close enough.</p>
			</div>
		</div>
	</div>
</div>

<div class="quote-section">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-8 text-center">
				<blockquote class="ash-quote">
					<p>"Hail to the king, baby."</p>
					<footer>— Ash Williams</footer>
				</blockquote>
			</div>
		</div>
	</div>
</div>

<div class="container cta-section">
	<div class="row justify-content-center">
		<div class="col-lg-6 text-center">
			<h2>Ready to Get Groovy?</h2>
			<p class="mb-4">Join the fight against the Army of Darkness</p>
			<button class="btn-boomstick" onclick="this.innerHTML='GROOVY! 🤘'">
				<span class="btn-text">Gimme Some Sugar</span>
			</button>
		</div>
	</div>
</div>

<footer class="site-footer">
	<div class="container">
		<div class="row">
			<div class="col text-center">
				<p>Good. Bad. I'm the guy with the gun. | <?='<?=';?>$libSaysHello;?></p>
				<p class="small">© <?='<?=';?>date('Y');?> S-Mart Housewares Department</p>
			</div>
		</div>
	</div>
</footer>

<?='<?=';?>$this->insertScript('index');?>