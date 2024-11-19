/*let slideIndex = 1;
showSlides(slideIndex);

// Next/previous controls
function changeSlide(n) {
    showSlides(slideIndex += n);
}

// Thumbnail image controls
function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let i;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");

    if (n > slides.length) {
        slideIndex = 1
    
    }

    if (n < 1) { 
        slideIndex = slides.length 
    }

    // Hide all slides
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }

    // Remove active class from dots
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }

    // Show the current slide and set the corresponding dot as active
    slides[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " active";
}*/

let currentSlideIndex = 0;

        function showSlides(index) {
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
            if (index >= slides.length) currentSlideIndex = 0;
            if (index < 0) currentSlideIndex = slides.length - 1;

            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === currentSlideIndex) slide.classList.add('active');
            });

            dots.forEach((dot, i) => {
            dot.classList.remove('active'); // Remove active class from all dots
            if (i === currentSlideIndex) dot.classList.add('active'); // Add to current
            });
        }

        function changeSlide(n) {
            showSlides(currentSlideIndex += n);
        }

        function currentSlide(n) {
            showSlides(currentSlideIndex = n - 1);
        }

        showSlides(currentSlideIndex); // Show the first slide initially