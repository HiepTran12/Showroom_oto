<?php include '../header.php' ?>
<link rel="stylesheet" href="/Website_OTO/DVvaHT/hero.css">
<style>
    .terms-content-section {
        padding: 80px 0;
        background-color: var(--bg-light);
        position: relative;
        overflow: hidden;
    }

    .terms-content-section::before,
    .terms-content-section::after {
        content: '';
        position: absolute;
        top: 0;
        width: 500px;
        height: 100%;
        background-image: url('../LOGO/image_2e1dbd.png');
        background-size: contain;
        background-repeat: no-repeat;
        opacity: 0.1;
        z-index: 0;
        pointer-events: none;
    }

    .terms-content-section::before {
        left: -100px;
        background-position: left center;
        transform: scaleX(-1);
    }

    .terms-content-section::after {
        right: -100px;
        background-position: right center;
    }

    .terms-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 20px;
        background-color: var(--bg-white);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        position: relative;
        z-index: 1;
        text-align: left;
    }

    .terms-container h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2.2em;
        margin-bottom: 25px;
        color: var(--text-dark);
        border-bottom: 2px solid var(--primary-gold);
        padding-bottom: 10px;
        text-align: center;
    }

    .terms-container h3 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5em;
        margin: 40px 0 15px;
        color: var(--primary-gold);
    }

    .terms-container p,
    .terms-container ul,
    .terms-container ol {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.05em;
        line-height: 1.8;
        color: var(--text-dark);
        margin-bottom: 20px;
    }

    .terms-container ul,
    .terms-container ol {
        margin-left: 30px;
    }

    .terms-container li {
        margin-bottom: 10px;
    }

    .terms-container strong {
        color: var(--primary-gold);
    }

    .terms-contact-button {
        text-align: center;
        margin-top: 50px;
        margin-bottom: 30px;
    }


    @media (max-width: 1200px) {
        .terms-content-section::before,
        .terms-content-section::after {
            width: 300px;
            left: -50px;
            right: -50px;
        }
    }

    @media (max-width: 992px) {
        .terms-content-section::before,
        .terms-content-section::after {
            width: 200px;
            left: -30px;
            right: -30px;
        }
    }

    @media (max-width: 768px) {
        .terms-content-section::before,
        .terms-content-section::after {
            display: none;
        }
    }
</style>

   <section class="hero hero-small" style="background-image: url('/showroom_oto/hinh/cayenne.jpg');">

        <div class="hero-content">
            <h1 align="left">Điều khoản dịch vụ</h1>
            <p>Hiểu rõ quyền và trách nhiệm của bạn khi sử dụng dịch vụ của Luxury Motors.</p>
            <a href="../index.php" class="btn">Về trang chủ</a>
        </div>
    </section>

    <section class="section terms-content-section">
        <div class="terms-container">
            <h2>Chào mừng bạn đến với Điều khoản và Điều kiện sử dụng dịch vụ của Luxury Motors</h2>
            <p>Bằng việc truy cập và sử dụng trang web cùng các dịch vụ do Luxury Motors cung cấp, bạn được xem là đã đọc, hiểu và đồng ý tuân thủ toàn bộ các Điều khoản và Điều kiện này. Đây là một thỏa thuận pháp lý ràng buộc giữa bạn và Luxury Motors. Nếu bạn không đồng ý với bất kỳ điều khoản nào, vui lòng không tiếp tục sử dụng dịch vụ của chúng tôi.</p>

            <h3>1. Phạm vi áp dụng và Chấp nhận Điều khoản</h3>
            <p>Các Điều khoản này áp dụng cho tất cả người dùng, bao gồm cả những người truy cập trang web để tìm hiểu thông tin, những người đăng ký nhận tư vấn, và những người thực hiện các giao dịch mua bán hoặc thuê xe. Việc bạn tiếp tục sử dụng dịch vụ sau khi các Điều khoản được cập nhật đồng nghĩa với sự chấp nhận của bạn đối với các thay đổi đó.</p>

            <h3>2. Quyền và Trách nhiệm của Người dùng</h3>
            <ul>
                <li>Bạn cam kết cung cấp thông tin chính xác, đầy đủ và trung thực khi sử dụng các biểu mẫu liên hệ, đăng ký hoặc giao dịch.</li>
                <li>Bạn có trách nhiệm bảo mật thông tin tài khoản (nếu có) và chịu trách nhiệm cho mọi hoạt động diễn ra dưới tài khoản của mình.</li>
                <li>Bạn đồng ý không sử dụng trang web để thực hiện bất kỳ hành vi vi phạm pháp luật, gây hại, quấy rối, hoặc cản trở hoạt động bình thường của Luxury Motors hoặc người dùng khác.</li>
                <li>Bạn không được phép sao chép, phân phối, hiển thị, hoặc sửa đổi bất kỳ nội dung nào trên trang web mà không có sự cho phép bằng văn bản từ Luxury Motors.</li>
            </ul>

            <h3>3. Quyền và Trách nhiệm của Luxury Motors</h3>
            <ul>
                <li>Luxury Motors cam kết cung cấp các dịch vụ chất lượng cao, thông tin chính xác về các dòng xe, và hỗ trợ khách hàng tận tâm.</li>
                <li>Chúng tôi có quyền từ chối, tạm ngừng hoặc chấm dứt cung cấp dịch vụ cho bất kỳ cá nhân hoặc tổ chức nào vi phạm các Điều khoản này.</li>
                <li>Chúng tôi có quyền cập nhật, thay đổi hoặc ngừng cung cấp bất kỳ tính năng, sản phẩm hoặc dịch vụ nào trên trang web mà không cần thông báo trước.</li>
                <li>Luxury Motors áp dụng các biện pháp bảo mật hợp lý để bảo vệ thông tin cá nhân của bạn theo Chính sách bảo mật của chúng tôi.</li>
            </ul>

            <h3>4. Quyền sở hữu trí tuệ</h3>
            <p>Tất cả nội dung, bao gồm hình ảnh, video, logo, thiết kế, văn bản, và mã nguồn trên trang web Luxury Motors, là tài sản độc quyền của Luxury Motors hoặc các bên cấp phép cho chúng tôi và được bảo vệ bởi luật bản quyền và các luật sở hữu trí tuệ liên quan. Nghiêm cấm mọi hành vi sao chép, phân phối, tái tạo hoặc sử dụng thương mại mà không có sự cho phép rõ ràng bằng văn bản.</p>

            <h3>5. Giới hạn trách nhiệm</h3>
            <p>Luxury Motors sẽ không chịu trách nhiệm đối với bất kỳ thiệt hại trực tiếp, gián tiếp, ngẫu nhiên, đặc biệt hoặc do hậu quả nào phát sinh từ việc sử dụng hoặc không thể sử dụng trang web hoặc dịch vụ của chúng tôi, bao gồm nhưng không giới hạn ở mất lợi nhuận, mất dữ liệu hoặc gián đoạn kinh doanh, ngay cả khi chúng tôi đã được thông báo về khả năng xảy ra các thiệt hại đó. Chúng tôi không đảm bảo rằng trang web sẽ luôn không bị lỗi hoặc không có virus.</p>

            <h3>6. Giải quyết tranh chấp và Luật áp dụng</h3>
            <p>Mọi tranh chấp phát sinh từ hoặc liên quan đến các Điều khoản này sẽ được giải quyết thông qua đàm phán thiện chí giữa các bên. Nếu không thể giải quyết bằng đàm phán, tranh chấp sẽ được đưa ra Tòa án có thẩm quyền tại Việt Nam theo pháp luật hiện hành của nước Cộng hòa xã hội chủ nghĩa Việt Nam.</p>

        </div>
    </section>

    <section class="cta">
        <div class="cta-content">
            <h2>Sẵn sàng sở hữu chiếc xe mơ ước?</h2>
            <p>Để lại thông tin, chuyên viên của chúng tôi sẽ liên hệ tư vấn miễn phí và hỗ trợ bạn chọn được chiếc xe phù hợp nhất.</p>
            
        </div>
    </section>

<?php include '../footer.php' ?>