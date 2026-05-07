const canvas = document.getElementById('particle-canvas');
const ctx = canvas.getContext('2d');
let particles = [];
const particleCount = 60;
const maxDistance = 100;
function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    initParticles();
}
class Particle {
    constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 1;
        this.vy = (Math.random() - 0.5) * 1;
        this.radius = Math.random() * 1.5 + 1;
        this.color = '#fff';
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2, false);
        ctx.fillStyle = this.color;
        ctx.fill();
    }
    update() {
        if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
        if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
        this.x += this.vx;
        this.y += this.vy;
    }
}
function initParticles() {
    particles = [];
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }
}
function drawLines() {
    for (let a of particles) {
        for (let b of particles) {
            const dx = a.x - b.x;
            const dy = a.y - b.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < maxDistance) {
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.strokeStyle = `rgba(255, 255, 255, ${1 - distance / maxDistance})`;
                ctx.lineWidth = 0.5;
                ctx.stroke();
            }
        }
    }
}
function animate() {
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawLines();
    for (let particle of particles) {
        particle.update();
        particle.draw();
    }
}
window.onload = function() {
    resizeCanvas();
    animate();
};
window.addEventListener('resize', resizeCanvas);

function showMessage(message, type = 'success') {
    const box = document.createElement('div');
    box.className = `center-message ${type}`;
    
    if (type === 'success' && !message.startsWith('✅')) {
        message = '✅ ' + message;
    }
    if (type === 'error' && !message.startsWith('❌') && !message.startsWith('⚠️')) {
        message = '❌ ' + message;
    }
    if (type === 'error' && message.toLowerCase().includes('scan')) {
        message = '⚠️ ' + message.replace(/^❌ /, '');
    }
    box.textContent = message;
    document.body.appendChild(box);
    setTimeout(() => box.classList.add('show'), 50);
    setTimeout(() => {
        box.classList.remove('show');
        setTimeout(() => box.remove(), 400);
    }, 2000);
}
