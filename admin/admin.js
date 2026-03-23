// Simple script to handle active menu items
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.nav-menu a');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
        });
    });
});


