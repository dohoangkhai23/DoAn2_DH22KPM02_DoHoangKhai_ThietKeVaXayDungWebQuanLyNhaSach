document.addEventListener('DOMContentLoaded', () => {
    const chatbotToggler = document.getElementById('chatbot-toggler');
    const chatbotContainer = document.getElementById('chatbot-container');
    const closeChatbot = document.getElementById('close-chatbot');
    const chatMessages = document.getElementById('chat-messages');
    const chatbotInput = document.getElementById('chatbot-input');
    const chatbotSend = document.getElementById('chatbot-send');

    // Mở/Đóng chatbot
    const toggleChatbot = () => {
        if (chatbotContainer.style.display === 'none' || chatbotContainer.style.display === '') {
            chatbotContainer.style.display = 'flex';
            chatbotToggler.style.display = 'none';
        } else {
            chatbotContainer.style.display = 'none';
            chatbotToggler.style.display = 'flex';
        }
    };

    chatbotToggler.addEventListener('click', toggleChatbot);
    closeChatbot.addEventListener('click', toggleChatbot);

    // Hàm thêm tin nhắn văn bản vào giao diện
    const addTextMessage = (message, sender) => {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', sender + '-message');
        messageElement.textContent = message;
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    // Hàm thêm danh sách sách vào giao diện
    const addBookList = (payload) => {
        // Hiển thị tin nhắn giới thiệu (e.g., "Tôi đã tìm thấy...")
        if (payload.message) {
            addTextMessage(payload.message, 'bot');
        }

        const bookListContainer = document.createElement('div');
        bookListContainer.classList.add('chat-message', 'bot-message', 'book-list');

        payload.books.forEach(book => {
            const bookItem = document.createElement('a');
            bookItem.classList.add('book-item');
            bookItem.href = `chitietsanpham.php?id=${book.MaSach}`;
            bookItem.target = '_blank'; // Open in new tab

            // Check if HinhAnh exists and is not null
            const imageUrl = book.HinhAnh ? `images/${book.HinhAnh}` : 'images/default-book.png'; // Fallback image

            bookItem.innerHTML = `
                <img src="${imageUrl}" alt="${book.TenSach}" class="book-image">
                <span class="book-title">${book.TenSach}</span>
            `;
            bookListContainer.appendChild(bookItem);
        });

        chatMessages.appendChild(bookListContainer);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };


    // Hàm xử lý gửi tin nhắn
    const handleSendMessage = () => {
        const message = chatbotInput.value.trim();
        if (message === '') return;

        addTextMessage(message, 'user');
        chatbotInput.value = '';
        chatbotInput.disabled = true;
        chatbotSend.disabled = true;

        const typingIndicator = document.createElement('div');
        typingIndicator.classList.add('chat-message', 'bot-message', 'typing');
        typingIndicator.innerHTML = '<span>.</span><span>.</span><span>.</span>';
        chatMessages.appendChild(typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        fetch('api_chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            chatMessages.removeChild(typingIndicator);

            // Handle new structured response
            if (data.type && data.payload) {
                switch (data.type) {
                    case 'text':
                        addTextMessage(data.payload, 'bot');
                        break;
                    case 'book_list':
                        addBookList(data.payload);
                        break;
                    default:
                        addTextMessage('Lỗi: Kiểu phản hồi không xác định.', 'bot');
                }
            } else {
                 // Fallback for old response structure or errors
                addTextMessage(data.reply || 'Có lỗi xảy ra, không nhận được phản hồi.', 'bot');
            }
        })
        .catch(error => {
            console.error('Error calling chatbot API:', error);
            chatMessages.removeChild(typingIndicator);
            addTextMessage('Xin lỗi, đã có lỗi kết nối đến máy chủ.', 'bot');
        })
        .finally(() => {
            chatbotInput.disabled = false;
            chatbotSend.disabled = false;
            chatbotInput.focus();
        });
    };

    chatbotSend.addEventListener('click', handleSendMessage);
    chatbotInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSendMessage();
        }
    });
});
