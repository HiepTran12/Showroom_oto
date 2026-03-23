<?php include '../header.php' ?>
<link rel="stylesheet" href="/Website_OTO/DVvaHT/hero.css">
    <style>
        .warranty-content-section::after {
            content: '';
            position: absolute;
            top: 0;
            right: -100px;
            width: 500px;
            height: 100%;
            background-image: url('../LOGO/image_2e1dbd.png'); /* Sử dụng hình ảnh từ trang hướng dẫn */
            background-size: contain;
            background-repeat: no-repeat;
            background-position: right center;
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
        }

        .warranty-container {
            max-width: 800px; /* Thu hẹp chiều rộng tối đa */
            margin: 0 auto; /* Căn giữa khối nội dung */
            padding: 40px 20px; /* Thêm padding trên dưới */
            text-align: left; /* Căn lề trái cho nội dung bên trong */
            background-color: var(--bg-white); /* Thêm màu nền trắng */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Thêm đổ bóng */
            border-radius: 10px; /* Thêm bo góc */
            position: relative; /* Đảm bảo nội dung nằm trên hình nền */
            z-index: 1;
        }

        .warranty-container h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2em;
            margin-bottom: 25px;
            color: var(--text-dark);
            border-bottom: 2px solid var(--primary-gold); /* Gạch chân vàng đồng */
            padding-bottom: 10px;
            text-align: center; /* Tiêu đề chính vẫn căn giữa */
            /* display: inline-block; đã bị loại bỏ để gạch chân kéo dài hết chiều rộng */
        }

        .warranty-container h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5em;
            margin-top: 40px;
            margin-bottom: 15px;
            color: var(--primary-gold); /* Tiêu đề phụ màu vàng đồng */
            text-align: left; /* Tiêu đề phụ căn lề trái */
        }

        .warranty-container p {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.05em;
            line-height: 1.8;
            margin-bottom: 20px;
            color: var(--text-dark);
            text-align: left; /* Các đoạn văn bản căn lề trái */
        }
        
        .warranty-container ul, .warranty-container ol {
            list-style-type: disc; /* Kiểu bullet mặc định cho ul */
            margin-left: 30px; /* Khoảng cách lề trái cho danh sách */
            /* Đã loại bỏ các thuộc tính căn giữa trước đó */
        }

        .warranty-container ol {
            list-style-type: decimal; /* Kiểu số cho ol */
        }

        /* Quan trọng: Nội dung bên trong mỗi thẻ li sẽ căn lề trái theo container */
        .warranty-container ul li, .warranty-container ol li {
            margin-bottom: 10px;
        }

        .warranty-container strong {
            color: var(--primary-gold); /* Từ khóa in đậm màu vàng đồng */
        }

        .warranty-contact-button {
            text-align: center;
            margin-top: 50px;
            margin-bottom: 30px;
        }

       
        /* Media Queries để đảm bảo responsive */
        @media (max-width: 1200px) {
            .warranty-content-section::before,
            .warranty-content-section::after {
                width: 300px;
                left: -50px;
                right: -50px;
            }
        }

        @media (max-width: 992px) {
            .warranty-content-section::before,
            .warranty-content-section::after {
                width: 200px;
                left: -30px;
                right: -30px;
            }
        }

        @media (max-width: 768px) {
            .warranty-content-section::before,
            .warranty-content-section::after {
                display: none; /* Ẩn hình nền trên màn hình nhỏ */
            }
        }
    </style>

    <section class="hero hero-small" style="background-image: url('/showroom_oto/hinh/cayman.jpg');">

        <div class="hero-content">
            <h1 align="left">Chính sách bảo hành xe</h1>
            <p>Sự an tâm tuyệt đối cho mỗi hành trình cùng Luxury Motors.</p>
            <a href="../index.php" class="btn">Về trang chủ</a>
        </div>
    </section>

    <section class="section warranty-content-section">
        <div class="warranty-container">
            <h2>Chính sách bảo hành xe tại Luxury Motors</h2>
            <p>Luxury Motors tự hào mang đến cho quý khách hàng những chiếc xe hơi sang trọng với cam kết chất lượng cao nhất. Để đảm bảo sự an tâm và hài lòng trọn vẹn, chúng tôi cung cấp chính sách bảo hành chi tiết và minh bạch cho tất cả các dòng xe bán ra.</p>

            <h3>1. Thời hạn và Điều kiện Bảo hành Tiêu chuẩn</h3>
            <p>Tất cả các xe mới được bán tại Luxury Motors đều được hưởng chính sách bảo hành tiêu chuẩn từ nhà sản xuất. Thời hạn bảo hành cụ thể sẽ được ghi rõ trong sổ bảo hành đi kèm xe và có hiệu lực từ ngày xe được bàn giao cho khách hàng đầu tiên.</p>
            <p><strong>Điều kiện áp dụng bảo hành:</strong></p>
            <ul>
                <li>Xe phải được mua và đăng ký chính hãng tại Luxury Motors hoặc các đại lý ủy quyền hợp pháp.</li>
                <li>Phiếu bảo hành gốc hoặc giấy tờ mua bán hợp lệ phải được xuất trình khi yêu cầu bảo hành.</li>
                <li>Xe phải được bảo dưỡng định kỳ và đúng lịch trình theo khuyến nghị của nhà sản xuất tại các trung tâm dịch vụ chính hãng của Luxury Motors hoặc các đối tác được ủy quyền.</li>
                <li>Lỗi phát sinh phải là lỗi kỹ thuật do nhà sản xuất hoặc lỗi lắp ráp, không phải do nguyên nhân bên ngoài.</li>
            </ul>

            <h3>2. Các Hạng mục được Bảo hành</h3>
            <p>Chính sách bảo hành của chúng tôi bao gồm các bộ phận và hệ thống chính của xe, đảm bảo hoạt động ổn định và an toàn:</p>
            <ul>
                <li><strong>Hệ thống động cơ:</strong> Các chi tiết bên trong động cơ, hệ thống nhiên liệu, hệ thống làm mát.</li>
                <li><strong>Hệ thống truyền động:</strong> Hộp số (số sàn, số tự động), trục dẫn động, vi sai.</li>
                <li><strong>Hệ thống điện và điện tử:</strong> Hệ thống khởi động, hệ thống sạc, ECU, các cảm biến chính, hệ thống chiếu sáng.</li>
                <li><strong>Hệ thống treo và lái:</strong> Giảm xóc, thanh cân bằng, hệ thống lái trợ lực.</li>
                <li><strong>Hệ thống phanh:</strong> Cụm phanh, hệ thống ABS/EBD.</li>
                <li><strong>Khung gầm và thân vỏ:</strong> Các lỗi sản xuất liên quan đến cấu trúc khung gầm và các mối hàn của thân vỏ xe.</li>
                <li><strong>Phụ kiện và thiết bị chính hãng:</strong> Các phụ kiện được lắp đặt tại nhà máy và được xác định là lỗi do sản xuất.</li>
            </ul>
            <p>Để biết chi tiết danh mục bảo hành của từng mẫu xe cụ thể, vui lòng tham khảo sổ tay bảo hành đi kèm xe hoặc liên hệ bộ phận dịch vụ của chúng tôi.</p>

            <h3>3. Các Trường hợp Không được Bảo hành</h3>
            <p>Luxury Motors sẽ không thực hiện bảo hành trong các trường hợp sau đây:</p>
            <ol>
                <li>Hư hỏng do tai nạn giao thông, va chạm, hỏa hoạn, ngập nước, thiên tai (lũ lụt, sét đánh, động đất), hoặc các sự kiện bất khả kháng khác.</li>
                <li>Hao mòn tự nhiên của các phụ tùng tiêu hao hoặc có tuổi thọ giới hạn như: lốp xe, má phanh, đĩa phanh, bugi, bóng đèn, lọc gió, dầu bôi trơn, nước làm mát, v.v.</li>
                <li>Hư hỏng do sử dụng xe sai mục đích, quá tải, tham gia đua xe, hoặc không tuân thủ khuyến nghị vận hành của nhà sản xuất.</li>
                <li>Hư hỏng do tự ý sửa chữa, tháo lắp, thay đổi cấu trúc, hoặc lắp đặt thêm phụ kiện không chính hãng, không được Luxury Motors hoặc nhà sản xuất ủy quyền.</li>
                <li>Thiệt hại do việc không thực hiện bảo dưỡng định kỳ hoặc bảo dưỡng không đúng cách tại các cơ sở không được Luxury Motors hoặc nhà sản xuất ủy quyền.</li>
                <li>Sự thay đổi về màu sắc, độ bóng hoặc các tính chất bề mặt của sơn xe do điều kiện môi trường khắc nghiệt, hóa chất, hoặc cách vệ sinh không phù hợp.</li>
                <li>Các trường hợp xe bị thay đổi số khung, số động cơ hoặc các thông tin nhận dạng khác so với giấy tờ gốc.</li>
            </ol>

            <h3>4. Quy trình Yêu cầu Bảo hành</h3>
            <p>Để đảm bảo quy trình bảo hành diễn ra nhanh chóng và hiệu quả, quý khách vui lòng thực hiện theo các bước sau:</p>
            <p><strong>Bước 1: Liên hệ Trung tâm Dịch vụ Luxury Motors</strong></p>
            <p>Ngay khi phát hiện bất kỳ dấu hiệu lỗi hoặc sự cố nào, quý khách vui lòng liên hệ ngay với trung tâm dịch vụ Luxury Motors gần nhất hoặc gọi đến số hotline hỗ trợ khách hàng của chúng tôi. Cung cấp thông tin xe (biển số, số VIN), mô tả chi tiết lỗi và thông tin liên hệ của quý khách.</p>
            <p><strong>Bước 2: Đưa xe đến kiểm tra</strong></p>
            <p>Mang xe đến trung tâm dịch vụ Luxury Motors để đội ngũ kỹ thuật viên chuyên nghiệp của chúng tôi tiến hành kiểm tra, chẩn đoán lỗi và xác định nguyên nhân. Vui lòng mang theo sổ bảo hành gốc và các giấy tờ liên quan đến việc mua xe.</p>
            <p><strong>Bước 3: Thẩm định và Xử lý Bảo hành</strong></p>
            <p>Sau khi lỗi được xác định thuộc phạm vi bảo hành, Luxury Motors sẽ tiến hành sửa chữa hoặc thay thế các bộ phận bị lỗi miễn phí bằng phụ tùng chính hãng. Chúng tôi sẽ thông báo cụ thể về thời gian dự kiến hoàn thành tùy thuộc vào mức độ phức tạp của lỗi và sự sẵn có của phụ tùng.</p>

            <h3>5. Hỗ trợ Sau Bảo hành và Dịch vụ Mở rộng</h3>
            <p>Luxury Motors luôn đồng hành cùng bạn trên mọi nẻo đường. Ngay cả khi xe của bạn đã hết thời hạn bảo hành tiêu chuẩn, chúng tôi vẫn cung cấp các gói dịch vụ bảo dưỡng, sửa chữa và cung cấp phụ tùng chính hãng với mức phí ưu đãi. Hãy liên hệ với chúng tôi để tìm hiểu về các gói dịch vụ mở rộng và bảo trì xe nhằm duy trì giá trị và hiệu suất tối ưu cho chiếc xe của bạn.</p>

           
        </div>
    </section>

    <section class="cta">
        <div class="cta-content">
            <h2>Sẵn sàng sở hữu chiếc xe mơ ước?</h2>
            <p>Để lại thông tin, chuyên viên của chúng tôi sẽ liên hệ tư vấn miễn phí và hỗ trợ bạn chọn được chiếc xe phù hợp nhất.</p>
       
        </div>
    </section>

<?php include '../footer.php' ?>