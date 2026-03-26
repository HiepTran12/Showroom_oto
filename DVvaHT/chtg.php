<?php include '../header.php' ?>
<link rel="stylesheet" href="/showroom_oto/DVvaHT/hero.css">
    <style>
        .section {
            padding: 80px 0;
            text-align: center;
        }

        .section-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8em;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .section-title p {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1em;
            color: var(--text-light);
            max-width: 800px;
            margin: 0 auto 50px;
        }

        .faq-section {
            background-color: var(--bg-light);
        }

        .faq-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
            /* text-align: center; */ /* Bỏ thuộc tính này ở container để các item con tự căn */
        }

        .faq-item {
            background-color: var(--bg-white);
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            /* text-align: left; */ /* Bỏ thuộc tính này ở item để nó không ghi đè */
            transition: box-shadow 0.3s ease;
        }

        .faq-item:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .faq-question {
            padding: 22px 30px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-dark);
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease, color 0.3s ease;
            text-align: center; /* Căn giữa nội dung câu hỏi */
        }

        .faq-question:hover {
            background-color: #f5f5f5;
            color: var(--primary-gold);
        }

        .faq-question i {
            transition: transform 0.3s ease;
            font-size: 0.9em;
            color: var(--text-light);
        }
        

        .faq-question.active i {
            transform: rotate(180deg);
            color: var(--primary-gold);
        }

        .faq-answer {
            padding: 0 30px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out, padding 0.5s ease-out;
            color: var(--text-light);
            line-height: 1.7;
            font-size: 1.05em;
            text-align: center; /* Căn giữa nội dung câu trả lời */
        }

        .faq-answer p {
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .faq-answer.active {
            max-height: 500px;
            padding-bottom: 20px;
        }

        
    </style>

   <section class="hero hero-small" style="background-image: url('/showroom_oto/hinh/a6.jpg');">

        <div class="hero-content">
            <h1 align="left">Câu hỏi thường gặp</h1>
            <p>Giải đáp những thắc mắc của bạn về Luxury Motors</p>
            <a href="../index.php" class="btn">Về trang chủ</a>
        </div>
    </section>

    <section class="section faq-section">
        <div class="section-title">
            <h2>Các câu hỏi phổ biến</h2>
            <p>Tìm câu trả lời cho những câu hỏi mà khách hàng thường quan tâm.</p>
        </div>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    Làm thế nào để đặt lịch lái thử xe?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Bạn có thể đặt lịch lái thử xe bằng cách điền vào biểu mẫu trên trang "Liên hệ" của chúng tôi, hoặc gọi trực tiếp đến số hotline 0901 234 567 để được hỗ trợ nhanh nhất.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Luxury Motors có hỗ trợ tài chính không?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, chúng tôi có các đối tác ngân hàng uy tín để cung cấp các gói vay mua xe với lãi suất ưu đãi, phù hợp với nhu cầu của từng khách hàng. Vui lòng liên hệ để được tư vấn chi tiết về các gói tài chính.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Chính sách bảo hành xe mới như thế nào?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Tất cả các xe mới bán ra tại Luxury Motors đều được hưởng chính sách bảo hành chính hãng theo tiêu chuẩn của nhà sản xuất. Thời gian và điều kiện bảo hành sẽ tùy thuộc vào từng dòng xe và thương hiệu cụ thể.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Tôi có thể bán lại xe cũ của mình cho Luxury Motors không?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Chúng tôi có dịch vụ thu mua xe cũ với giá cạnh tranh. Nếu bạn muốn bán lại xe, vui lòng mang xe đến showroom để được định giá hoặc liên hệ để chúng tôi sắp xếp lịch hẹn kiểm tra xe.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Dịch vụ bảo dưỡng và sửa chữa có những gì?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Luxury Motors cung cấp đầy đủ các dịch vụ bảo dưỡng định kỳ, sửa chữa, thay thế phụ tùng chính hãng và chăm sóc xe chuyên nghiệp. Đội ngũ kỹ thuật viên của chúng tôi được đào tạo bài bản và có kinh nghiệm với các dòng xe sang.</p>
                </div>
            </div>

        
        </div>
    </section>

    

<?php include '../footer.php' ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const questions = document.querySelectorAll('.faq-question');

        questions.forEach(function (question) {
            question.addEventListener('click', function () {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('i');

                // Toggle hiển thị câu trả lời
                answer.classList.toggle('active');
                this.classList.toggle('active');

                // Ẩn các câu trả lời khác nếu cần (nếu muốn dạng accordion 1 câu mở)
                questions.forEach(q => {
                    if (q !== this) {
                        q.classList.remove('active');
                        q.nextElementSibling.classList.remove('active');
                    }
                });
            });
        });
    });
</script>
