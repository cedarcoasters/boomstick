<style type="text/css">

:root {
    --blood-red: #8B0000;
    --dark-blood: #4a0000;
    --cabin-wood: #2d1810;
    --fog-grey: #1a1a1a;
    --bone-white: #e8e0d5;
    --chainsaw-orange: #ff6b35;
}

body {
    background: linear-gradient(180deg, #0d0d0d 0%, #1a0a0a 50%, #0d0d0d 100%);
    color: var(--bone-white);
    font-family: 'Georgia', serif;
    overflow-x: hidden;
}

/* Hero Section */
.hero-section {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background:
        radial-gradient(ellipse at center, rgba(139,0,0,0.2) 0%, transparent 70%),
        linear-gradient(180deg, #0d0d0d 0%, #1a0808 100%);
    padding: 4rem 0;
}

.blood-drip {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(180deg, var(--blood-red) 0%, transparent 100%);
    animation: drip 3s ease-in-out infinite;
}

@keyframes drip {
    0%, 100% { opacity: 0.7; height: 20px; }
    50% { opacity: 1; height: 35px; }
}

.boomstick-img {
    max-width: 300px;
    height: auto;
    animation: shake 0.5s ease-in-out infinite;
    display: inline-block;
    margin-bottom: 1rem;
    filter: drop-shadow(0 0 10px rgba(139, 0, 0, 0.5));
}

@keyframes shake {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
}

.main-title {
    margin-bottom: 1.5rem;
}

.this-is {
    display: block;
    font-size: 1.5rem;
    color: var(--bone-white);
    opacity: 0.8;
    letter-spacing: 3px;
    text-transform: uppercase;
    animation: flicker 4s ease-in-out infinite;
}

.boomstick-text {
    display: block;
    font-size: 5rem;
    font-weight: bold;
    color: var(--blood-red);
    text-shadow:
        0 0 10px var(--blood-red),
        0 0 20px var(--dark-blood),
        0 0 40px var(--dark-blood),
        3px 3px 0 #000;
    letter-spacing: 5px;
    animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        text-shadow:
            0 0 10px var(--blood-red),
            0 0 20px var(--dark-blood),
            0 0 40px var(--dark-blood),
            3px 3px 0 #000;
    }
    50% {
        text-shadow:
            0 0 20px var(--blood-red),
            0 0 40px var(--blood-red),
            0 0 60px var(--dark-blood),
            3px 3px 0 #000;
    }
}

@keyframes flicker {
    0%, 100% { opacity: 0.8; }
    92% { opacity: 0.8; }
    93% { opacity: 0.4; }
    94% { opacity: 0.9; }
    95% { opacity: 0.3; }
    96% { opacity: 0.8; }
}

.tagline {
    font-size: 1.4rem;
    color: var(--chainsaw-orange);
    font-style: italic;
    margin-top: 1rem;
}

/* Features Section */
.features-section {
    padding: 5rem 0;
}

.section-title {
    font-size: 2.5rem;
    color: var(--blood-red);
    margin-bottom: 2rem;
    text-transform: uppercase;
    letter-spacing: 3px;
}

.feature-card {
    background: linear-gradient(145deg, #1a1a1a 0%, #0d0d0d 100%);
    border: 1px solid var(--dark-blood);
    border-radius: 10px;
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--blood-red), transparent);
    transition: left 0.5s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    border-color: var(--blood-red);
    box-shadow: 0 10px 30px rgba(139, 0, 0, 0.3);
}

.feature-card:hover::before {
    left: 100%;
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.feature-card h3 {
    color: var(--chainsaw-orange);
    font-size: 1.4rem;
    margin-bottom: 1rem;
}

.feature-card p {
    color: var(--bone-white);
    opacity: 0.8;
    line-height: 1.6;
}

/* Quote Section */
.quote-section {
    padding: 5rem 0;
    background: linear-gradient(180deg, transparent 0%, rgba(139,0,0,0.1) 50%, transparent 100%);
}

.ash-quote {
    font-size: 2rem;
    font-style: italic;
    position: relative;
    padding: 2rem;
}

.ash-quote p {
    color: var(--bone-white);
    margin-bottom: 1rem;
}

.ash-quote::before,
.ash-quote::after {
    content: '"';
    font-size: 4rem;
    color: var(--blood-red);
    opacity: 0.5;
    position: absolute;
}

.ash-quote::before {
    top: 0;
    left: 0;
}

.ash-quote::after {
    bottom: 0;
    right: 0;
}

.ash-quote footer {
    color: var(--chainsaw-orange);
    font-size: 1.2rem;
}

/* CTA Section */
.cta-section {
    padding: 5rem 0;
}

.cta-section h2 {
    color: var(--bone-white);
    font-size: 2.2rem;
    margin-bottom: 1rem;
}

.btn-boomstick {
    background: linear-gradient(145deg, var(--blood-red) 0%, var(--dark-blood) 100%);
    color: var(--bone-white);
    border: 2px solid var(--blood-red);
    padding: 1rem 3rem;
    font-size: 1.3rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-boomstick::before {
    content: '💥';
    position: absolute;
    left: -30px;
    opacity: 0;
    transition: all 0.3s ease;
}

.btn-boomstick::after {
    content: '💥';
    position: absolute;
    right: -30px;
    opacity: 0;
    transition: all 0.3s ease;
}

.btn-boomstick:hover {
    background: linear-gradient(145deg, #a50000 0%, var(--blood-red) 100%);
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(139, 0, 0, 0.5);
}

.btn-boomstick:hover::before {
    left: 15px;
    opacity: 1;
}

.btn-boomstick:hover::after {
    right: 15px;
    opacity: 1;
}

.btn-boomstick:active {
    transform: scale(0.98);
}

/* Footer */
.site-footer {
    padding: 3rem 0;
    border-top: 1px solid var(--dark-blood);
    margin-top: 3rem;
}

.site-footer p {
    color: var(--bone-white);
    opacity: 0.7;
    margin-bottom: 0.5rem;
}

.site-footer .small {
    font-size: 0.85rem;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .boomstick-text {
        font-size: 3rem;
    }

    .this-is {
        font-size: 1rem;
    }

    .ash-quote {
        font-size: 1.4rem;
    }

    .boomstick-img {
        max-width: 200px;
    }
}

/* Old blink animation - keeping for compatibility */
.blink {
    animation: blink 1s infinite;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0; }
    100% { opacity: 1; }
}

/* Easter egg groovy flash */
@keyframes groovy-flash {
    0%, 100% { filter: none; }
    25% { filter: hue-rotate(90deg) saturate(2); }
    50% { filter: hue-rotate(180deg) saturate(2); }
    75% { filter: hue-rotate(270deg) saturate(2); }
}

</style>