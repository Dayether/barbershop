/**
 * Effects.js - Enhanced visual effects for Tipuno Barbershop
 * Includes parallax effects, before/after sliders, and animations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize parallax effects
    initParallax();
    
    // Initialize before/after sliders
    initBeforeAfterSlider();
    
    // Initialize text effects
    initTextEffects();
});

/**
 * Initialize parallax background effect
 */
function initParallax() {
    const parallaxElements = document.querySelectorAll('.parallax-background');
    
    if (parallaxElements.length > 0) {
        window.addEventListener('scroll', function() {
            const scrollPosition = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const elementTop = element.offsetTop;
                const elementHeight = element.offsetHeight;
                
                // Only apply effect when element is in view
                if (scrollPosition + window.innerHeight > elementTop && 
                    scrollPosition < elementTop + elementHeight) {
                    const speed = element.getAttribute('data-speed') || 0.15;
                    const yPos = -(scrollPosition - elementTop) * speed;
                    
                    element.style.backgroundPosition = `center ${yPos}px`;
                }
            });
        });
    }
}

/**
 * Initialize before/after image comparison slider
 */
function initBeforeAfterSlider() {
    const containers = document.querySelectorAll('.before-after-container');
    
    containers.forEach(container => {
        const slider = container.querySelector('.before-after-slider');
        const beforeImage = container.querySelector('.before-image');
        const afterImage = container.querySelector('.after-image');
        const handle = container.querySelector('.before-after-handle');
        
        if (!slider || !beforeImage || !afterImage || !handle) return;
        
        let isDragging = false;
        
        // Initial position
        moveSlider(50);
        
        // Mouse events
        handle.addEventListener('mousedown', startDrag);
        container.addEventListener('mousemove', drag);
        container.addEventListener('mouseup', stopDrag);
        container.addEventListener('mouseleave', stopDrag);
        
        // Touch events
        handle.addEventListener('touchstart', startDrag);
        container.addEventListener('touchmove', drag);
        container.addEventListener('touchend', stopDrag);
        
        // Double click to reset
        container.addEventListener('dblclick', function() {
            moveSlider(50);
        });
        
        function startDrag(e) {
            e.preventDefault();
            isDragging = true;
            
            // Add dragging class for visual feedback
            handle.classList.add('dragging');
            container.classList.add('comparing');
        }
        
        function drag(e) {
            if (!isDragging) return;
            
            e.preventDefault();
            
            const rect = container.getBoundingClientRect();
            const containerWidth = rect.width;
            
            // Get cursor position
            let x;
            if (e.type === 'touchmove') {
                x = e.touches[0].clientX - rect.left;
            } else {
                x = e.clientX - rect.left;
            }
            
            // Calculate position as percentage
            let percentage = (x / containerWidth) * 100;
            percentage = Math.max(0, Math.min(100, percentage));
            
            moveSlider(percentage);
        }
        
        function stopDrag() {
            isDragging = false;
            handle.classList.remove('dragging');
            container.classList.remove('comparing');
        }
        
        function moveSlider(percentage) {
            slider.style.left = `${percentage}%`;
            afterImage.style.width = `${percentage}%`;
        }
    });
}

/**
 * Initialize text gradient effects
 */
function initTextEffects() {
    const gradientTexts = document.querySelectorAll('.text-gradient');
    
    gradientTexts.forEach(text => {
        // Add subtle animation to gradient texts
        text.addEventListener('mouseenter', function() {
            this.style.backgroundSize = '200% auto';
            this.style.backgroundPosition = 'right center';
        });
        
        text.addEventListener('mouseleave', function() {
            this.style.backgroundSize = '200% auto';
            this.style.backgroundPosition = 'left center';
        });
    });
}

/**
 * Animated background grain effect
 */
if (document.body.classList.contains('gold-grain')) {
    const grain = document.createElement('div');
    grain.className = 'grain';
    document.body.appendChild(grain);
    
    // Animate grain with subtle movement
    let x = 0;
    let y = 0;
    const animate = () => {
        x += 0.3;
        y += 0.3;
        grain.style.backgroundPosition = `${x}px ${y}px`;
        requestAnimationFrame(animate);
    };
    
    animate();
}
