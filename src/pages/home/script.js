function toggleMenu() {
    var menu = document.getElementById("menu");
    var menuToggle = document.getElementById("menuToggle");

    menu.classList.toggle("active");
    menuToggle.classList.toggle("active");

    if (window.innerWidth > 512) {
        menu.classList.remove("active");
        menuToggle.classList.remove("active");
    }
}
  
window.addEventListener("resize", function() {
    if (window.innerWidth > 512) {
        var menu = document.getElementById("menu");
        var menuToggle = document.getElementById("menuToggle");
        
        menu.classList.remove("active");
        menuToggle.classList.remove("active");
    }
});
  