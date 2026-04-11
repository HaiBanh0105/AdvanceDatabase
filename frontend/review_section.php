<link rel="stylesheet" href="../assets/css/review.css">

<section id="reviews" class="py-24 bg-indigo-50/50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-black text-slate-800 mb-4">Khách hàng nói gì về chúng tôi?</h2>
            <div class="h-1 w-20 bg-indigo-600 mx-auto rounded-full mb-6"></div>
            <p class="text-slate-500 max-w-2xl mx-auto">Hàng ngàn khách hàng đã trải nghiệm và hài lòng với dịch vụ tại Grand Horizon. Hãy chia sẻ cảm nhận của bạn nhé!</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5 h-fit sticky top-24">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <!-- Trạng thái chưa đăng nhập -->
                    <div class="text-center p-8 bg-white rounded-[2rem] border border-slate-100 shadow-sm">
                        <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fa-solid fa-lock"></i></div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Bạn chưa đăng nhập</h3>
                        <p class="text-slate-500 text-sm mb-6">Vui lòng đăng nhập để có thể chia sẻ trải nghiệm của bạn.</p>
                        <a href="login.php" class="inline-block px-8 py-3 w-full bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">Đăng nhập ngay</a>
                    </div>
                <?php elseif (!$has_reviewed): ?>
                    <!-- Trạng thái chưa đánh giá: Thêm mới -->
                    <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                        <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3"><i class="fa-solid fa-pen-to-square text-indigo-500"></i>Đánh giá và nhận xét</h3>
                        <form action="../actions/process_review.php" method="POST">
                            <div class="mb-5">
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required checked /><label for="star5" title="Tuyệt vời"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Rất tốt"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Tạm được"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Không hài lòng"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Rất tệ"><i class="fa-solid fa-star"></i></label>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Viết cảm nhận của bạn</label>
                                <textarea name="comment" rows="4" required placeholder="Chia sẻ trải nghiệm của bạn tại Grand Horizon..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-700 resize-none"></textarea>
                            </div>
                            <button type="submit" name="action" value="add" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition w-full shadow-lg shadow-indigo-100 flex items-center justify-center gap-2"><i class="fa-solid fa-paper-plane"></i> Gửi đánh giá ngay</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Trạng thái đã đánh giá: Cập nhật & Xóa -->
                    <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>
                        <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3"><i class="fa-solid fa-pen-to-square text-amber-500"></i>Đánh giá và nhận xét</h3>
                        <form action="../actions/process_review.php" method="POST">
                            <div class="mb-5">
                                <div class="star-rating">
                                    <input type="radio" id="estar5" name="rating" value="5" <?php echo ($check_review['rating'] == 5) ? 'checked' : ''; ?> /><label for="estar5"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="estar4" name="rating" value="4" <?php echo ($check_review['rating'] == 4) ? 'checked' : ''; ?> /><label for="estar4"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="estar3" name="rating" value="3" <?php echo ($check_review['rating'] == 3) ? 'checked' : ''; ?> /><label for="estar3"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="estar2" name="rating" value="2" <?php echo ($check_review['rating'] == 2) ? 'checked' : ''; ?> /><label for="estar2"><i class="fa-solid fa-star"></i></label>
                                    <input type="radio" id="estar1" name="rating" value="1" <?php echo ($check_review['rating'] == 1) ? 'checked' : ''; ?> /><label for="estar1"><i class="fa-solid fa-star"></i></label>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Viết cảm nhận của bạn</label>
                                <textarea name="comment" rows="4" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm font-medium text-slate-700 resize-none"><?php echo htmlspecialchars($check_review['comment']); ?></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" name="action" value="edit" class="flex-1 bg-amber-500 text-white py-3 rounded-2xl font-bold hover:bg-amber-600 transition shadow-lg shadow-amber-100 flex items-center justify-center gap-2 text-sm"><i class="fa-solid fa-rotate-right"></i> Cập nhật</button>
                                <button type="submit" name="action" value="delete" onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');" class="px-5 bg-red-50 text-red-500 rounded-2xl font-bold hover:bg-red-500 hover:text-white transition flex items-center justify-center gap-2"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CỘT PHẢI: KHUNG CUỘN DANH SÁCH -->
            <div class="lg:col-span-7">
                <?php if (empty($reviews)): ?>
                    <div class="text-center text-slate-400 italic text-sm p-10 bg-white rounded-[2rem] border border-slate-100">Chưa có đánh giá nào. Hãy là người đầu tiên!</div>
                <?php else: ?>
                    <div class="pr-2 space-y-4 max-h-[500px] overflow-y-auto scroll-smooth" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                        <?php foreach ($reviews as $rv): 
                            $date = '';
                            if (isset($rv['created_at']) && $rv['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                $date = $rv['created_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i');
                            }
                        ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 hover:shadow-md transition duration-300 flex gap-5">
                            <div class="w-12 h-12 shrink-0 bg-gradient-to-br from-indigo-500 to-purple-500 text-white rounded-full flex items-center justify-center font-black text-xl shadow-md">
                                <?php echo mb_substr($rv['user_name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($rv['user_name']); ?></h4>
                                        <div class="text-yellow-400 text-xs tracking-wider mt-1">
                                            <?php echo str_repeat('★', $rv['rating']) . str_repeat('☆', 5 - $rv['rating']); ?>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400"><?php echo $date; ?></span>
                                </div>
                                <p class="text-slate-600 text-sm leading-relaxed break-all">"<?php echo htmlspecialchars($rv['comment']); ?>"</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>