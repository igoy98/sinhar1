// Set active navigation link based on current page
function setActiveNav() {
  const currentPage = window.location.pathname.split('/').pop() || 'index.php';
  const navLinks = document.querySelectorAll('.site-header a');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    link.classList.remove('active');
    
    if ((currentPage === 'index.php' || currentPage === '') && href === 'index.php') {
      link.classList.add('active');
    } else if (currentPage === href) {
      link.classList.add('active');
    }
  });
}

// Focus first input field
document.addEventListener('DOMContentLoaded', function() {
  setActiveNav();
  
  var el = document.querySelector('input[type=text]');
  if(el) el.focus();
  
  // Smooth scroll behavior
  document.documentElement.scrollBehavior = 'smooth';
});

// Add hover feedback to navigation
document.addEventListener('mouseover', function(e) {
  if (e.target.closest('.site-header a')) {
    e.target.closest('.site-header a').style.transition = 'all 0.3s ease';
  }
});
