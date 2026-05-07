// Particle background
        var canvas = document.getElementById('particle-canvas');
        var ctx = canvas.getContext('2d');
        var particles = [];
        var particleCount = 60;
        var maxDistance = 100;

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            initParticles();
        }
        function Particle() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 1;
            this.vy = (Math.random() - 0.5) * 1;
            this.radius = Math.random() * 1.5 + 1;
            this.color = '#fff';
        }
        Particle.prototype.draw = function() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2, false);
            ctx.fillStyle = this.color;
            ctx.fill();
        };
        Particle.prototype.update = function() {
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
            this.x += this.vx;
            this.y += this.vy;
        };
        function initParticles() {
            particles = [];
            for (var i = 0; i < particleCount; i++) particles.push(new Particle());
        }
        function drawLines() {
            for (var i = 0; i < particles.length; i++) {
                for (var j = i + 1; j < particles.length; j++) {
                    var a = particles[i];
                    var b = particles[j];
                    var dx = a.x - b.x;
                    var dy = a.y - b.y;
                    var distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < maxDistance) {
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = 'rgba(255, 255, 255, ' + (1 - distance / maxDistance) + ')';
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
            for (var k = 0; k < particles.length; k++) {
                particles[k].update();
                particles[k].draw();
            }
        }
        window.onload = function() { resizeCanvas(); animate(); };
        window.addEventListener('resize', resizeCanvas);