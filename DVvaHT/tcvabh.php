<?php include '../header.php' ?>
<link rel="stylesheet" href="/Website_OTO/DVvaHT/hero.css">
<style>
.warranty-content-section {
  padding: 80px 0;
  background-color: var(--bg-light);
  position: relative;
  overflow: hidden;
}
.warranty-content-section::before,
.warranty-content-section::after {
  content: '';
  position: absolute;
  width: 500px;
  height: 100%;
  background-size: contain;
  background-repeat: no-repeat;
  opacity: 0.1;
  z-index: 0;
  pointer-events: none;
}
.warranty-content-section::before {
  top: 0;
  left: -100px;
  background-position: left center;
  transform: scaleX(-1);
}
.warranty-content-section::after {
  top: 0;
  right: -100px;
  background-position: right center;
}
.warranty-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 20px;
  background-color: var(--bg-white);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border-radius: 10px;
  position: relative;
  z-index: 1;
}
.warranty-container h2 {
  font-size: 2.2em;
  margin-bottom: 25px;
  color: var(--text-dark);
  border-bottom: 2px solid var(--primary-gold);
  padding-bottom: 10px;
  text-align: center;
}
.warranty-container h3 {
  font-size: 1.5em;
  margin: 40px 0 15px;
  color: var(--primary-gold);
}
.warranty-container p,
.warranty-container ul li {
  font-size: 1.05em;
  line-height: 1.8;
  color: var(--text-dark);
}
.warranty-container ul {
  margin-left: 30px;
  margin-bottom: 20px;
}
.warranty-container strong {
  color: var(--primary-gold);
}
.warranty-contact-button {
  text-align: center;
  margin: 50px 0 30px;
}
@media (max-width: 768px) {
  .warranty-content-section::before,
  .warranty-content-section::after {
    display: none;
  }
}
</style>

<section class="hero hero-small" style="background-image: url('/showroom_oto/hinh/911turbo.jpg');">

  <div class="hero-content">
    <h1>Tài chính & Bảo hiểm</h1>
    <p>Giải pháp linh hoạt - An tâm sở hữu xế sang cùng Luxury Motors.</p>
    <a href="../index.php" class="btn">Về trang chủ</a>
  </div>
</section>

<section class="section warranty-content-section">
  <div class="warranty-container">
    <h2>Giải pháp Tài chính & Bảo hiểm tại Luxury Motors</h2>
    <p>Chúng tôi cung cấp các gói hỗ trợ tài chính linh hoạt và bảo hiểm toàn diện, giúp bạn dễ dàng sở hữu và sử dụng xe sang một cách an tâm và thuận tiện.</p>

    <h3>1. Hỗ trợ Tài chính Linh hoạt</h3>
    <ul>
      <li>Cho vay mua xe với lãi suất ưu đãi và thủ tục nhanh chóng.</li>
      <li>Chương trình trả góp 0% lãi suất trong thời gian đầu.</li>
      <li>Hỗ trợ hồ sơ vay vốn và tư vấn ngân hàng đối tác.</li>
      <li>Tùy chọn thanh toán theo kỳ hạn linh hoạt theo nhu cầu cá nhân.</li>
    </ul>

    <h3>2. Gói Bảo hiểm Toàn diện</h3>
    <ul>
      <li>Bảo hiểm vật chất xe, trách nhiệm dân sự và tai nạn người ngồi trên xe.</li>
      <li>Hợp tác với các công ty bảo hiểm uy tín hàng đầu Việt Nam.</li>
      <li>Hỗ trợ xử lý hồ sơ bồi thường nhanh chóng khi có sự cố.</li>
      <li>Tư vấn chọn gói bảo hiểm phù hợp với từng dòng xe và nhu cầu sử dụng.</li>
    </ul>

    <h3>3. Lợi ích khi sử dụng dịch vụ tại Luxury Motors</h3>
    <ul>
      <li>Tiết kiệm thời gian với quy trình đăng ký vay và bảo hiểm ngay tại showroom.</li>
      <li>Tư vấn cá nhân hóa từ chuyên viên tài chính nhiều kinh nghiệm.</li>
      <li>Hỗ trợ sau bán hàng và đồng hành trong suốt thời gian sử dụng xe.</li>
    </ul>

   
  </div>
</section>

<section class="section cta">
  <div class="cta-content">
    <h2>Khởi đầu hành trình sở hữu xe mơ ước</h2>
    <p>Đăng ký nhận tư vấn tài chính và bảo hiểm để nhận các ưu đãi độc quyền dành riêng cho bạn tại Luxury Motors.</p>
   
  </div>
</section>

<?php include '../footer.php' ?>
