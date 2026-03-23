document.addEventListener("DOMContentLoaded", () => {
  // ===== Sticky Header =====
  const header = document.getElementById("header");
  window.addEventListener("scroll", () => {
    if (window.scrollY > 100) {
      header?.classList.add("scrolled");
    } else {
      header?.classList.remove("scrolled");
    }
  });

  // ===== Mobile Menu Toggle =====
  const menuToggle = document.querySelector(".menu-toggle");
  const navLinks = document.querySelector(".nav-links");
  menuToggle?.addEventListener("click", () => {
    navLinks?.classList.toggle("active");
  });

  // ===== Smooth Scrolling for Anchor Links =====
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href");
      if (!targetId || targetId === "#") return;

      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: "smooth",
        });
        navLinks?.classList.remove("active");
      }
    });
  });

  // ===== Scroll Animation for Elements =====
  const animatedElements = document.querySelectorAll('.animate-on-scroll');
  animatedElements.forEach(el => {
    el.style.opacity = "0";
    el.style.transform = "translateY(30px)";
    el.style.transition = "all 0.5s ease";
  });

  function animateOnScroll() {
    animatedElements.forEach(el => {
      const position = el.getBoundingClientRect().top;
      const screenPosition = window.innerHeight / 1.3;
      if (position < screenPosition) {
        el.style.opacity = "1";
        el.style.transform = "translateY(0)";
      }
    });
  }

  window.addEventListener("scroll", animateOnScroll);
  window.addEventListener("load", animateOnScroll);

  // ===== Featured Cars Carousel (Tự động cuộn) =====
  const container = document.querySelector('.featured-carousel');

  if (container) {
    const card = container.querySelector('.featured-card');
    const cardWidth = card ? card.offsetWidth : 300;
    const totalCards = container.children.length;
    let currentIndex = 0;

    const scrollToCard = (index) => {
      const leftPos = index * cardWidth;
      container.scrollTo({ left: leftPos, behavior: 'smooth' });
    };

    // Tự động cuộn mỗi 2 giây
    setInterval(() => {
      currentIndex++;
      if (currentIndex >= totalCards) currentIndex = 0;
      scrollToCard(currentIndex);
    }, 2000);

    // Nút điều hướng trái/phải (nếu muốn dùng thêm)
    btnLeft?.addEventListener('click', () => {
      currentIndex = Math.max(0, currentIndex - 1);
      scrollToCard(currentIndex);
    });

    btnRight?.addEventListener('click', () => {
      currentIndex = Math.min(totalCards - 1, currentIndex + 1);
      scrollToCard(currentIndex);
    });
  }

  // ===== Xử lý click Đăng ký mở dropdown + focus nút =====
  document.querySelectorAll('[href="#dangky"]').forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      const accountDropdown = document.querySelector('.dropdown a[href="#"]');
      accountDropdown?.click();
      window.scrollTo({ top: 0, behavior: "smooth" });
      document.querySelector(".register-btn")?.focus();
    });
  });
});
