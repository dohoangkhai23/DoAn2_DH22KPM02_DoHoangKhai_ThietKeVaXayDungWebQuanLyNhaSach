<!-- Giao diện Chatbot -->
<button id="chatbot-toggler">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
</button>

<div id="chatbot-container">
    <div class="chatbot-header">
        <span>Tư vấn sách</span>
        <button id="close-chatbot">&times;</button>
    </div>
    <div id="chat-messages">
        <div class="chat-message bot-message">
            Chào bạn, tôi có thể giúp gì cho bạn hôm nay?
        </div>
    </div>
    <div class="chatbot-input-form">
        <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi của bạn...">
        <button id="chatbot-send">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </button>
    </div>
</div>
