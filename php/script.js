$(document).ready(function() {
    $('.nonloop-block-13').owlCarousel({
        items: 1,
        loop: true,
        margin: 0,
        nav: true,
        dots: false,
        navText: ['←', '→'],
        responsive: {
            600: {
                items: 2,
                margin: 0
            },
            1000: {
                items: 3,
                margin: 0
            },
            1200: {
                items: 4,
                margin: 0
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item, .remaining-faqs .faq-item');
    const showMoreBtn = document.querySelector('.show-more-btn');
    const showMoreContainer = document.querySelector('.show-more-container');
    const remainingFaqs = document.querySelector('.remaining-faqs');
    
    // FAQ toggle functionality
    faqItems.forEach(item => {
        const question = item.querySelector('.question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    });
    
    // Show More functionality
    showMoreBtn.addEventListener('click', function() {
        remainingFaqs.classList.add('show');
        showMoreContainer.style.display = 'none';
    });
});