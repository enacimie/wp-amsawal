(function() {
    'use strict';

    var currentQuestions = [];
    var currentIndex = 0;
    var correctCount = 0;
    var answered = false;

    function init() {
        var quickBtn = document.getElementById('start-quick-review');
        if (quickBtn) {
            quickBtn.addEventListener('click', function() {
                startReview(null, this.dataset.nonce);
            });
        }

        var lessonBtns = document.querySelectorAll('[data-review-lesson]');
        lessonBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                startReview(this.dataset.reviewLesson, this.dataset.nonce);
            });
        });

        var closeBtn = document.getElementById('close-review-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        var backdrop = document.querySelector('.duo-review-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        var nextBtn = document.getElementById('next-question');
        if (nextBtn) {
            nextBtn.addEventListener('click', nextQuestion);
        }
    }

    function startReview(lessonId, nonce) {
        var data = new FormData();
        data.append('action', 'amsawal_start_review');
        data.append('nonce', nonce);
        if (lessonId) {
            data.append('lesson_id', lessonId);
        }

        fetch(wpAmsawalReview.ajaxUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.data.questions.length > 0) {
                currentQuestions = res.data.questions;
                currentIndex = 0;
                correctCount = 0;
                document.getElementById('review-total').textContent = res.data.total;
                openModal();
                showQuestion();
            } else {
                alert(res.data.message || 'No hay preguntas disponibles');
            }
        })
        .catch(function(err) {
            console.error('Review error:', err);
            alert('Error al cargar la sesión de repaso');
        });
    }

    function openModal() {
        var modal = document.getElementById('review-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        var modal = document.getElementById('review-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function showQuestion() {
        answered = false;
        var q = currentQuestions[currentIndex];
        var questionEl = document.getElementById('review-question');
        var optionsEl = document.getElementById('review-options');
        var feedbackEl = document.getElementById('review-feedback');
        var nextBtn = document.getElementById('next-question');

        document.getElementById('review-current').textContent = currentIndex + 1;

        var progress = ((currentIndex) / currentQuestions.length) * 100;
        document.getElementById('review-progress-fill').style.width = progress + '%';

        questionEl.textContent = q.question;

        var letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        var optionsHtml = '';
        q.options.forEach(function(opt, i) {
            optionsHtml += '<button class="duo-review-option" data-index="' + i + '" data-correct="' + (opt.correct ? '1' : '0') + '">' +
                '<span class="duo-review-option-letter">' + letters[i] + '</span>' +
                '<span>' + escapeHtml(opt.text) + '</span>' +
                '</button>';
        });
        optionsEl.innerHTML = optionsHtml;

        feedbackEl.style.display = 'none';
        feedbackEl.className = 'duo-review-feedback';
        nextBtn.style.display = 'none';

        var optionBtns = optionsEl.querySelectorAll('.duo-review-option');
        optionBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (answered) return;
                selectOption(this);
            });
        });
    }

    function selectOption(btn) {
        answered = true;
        var isCorrect = btn.dataset.correct === '1';
        var q = currentQuestions[currentIndex];

        var allOptions = document.querySelectorAll('.duo-review-option');
        allOptions.forEach(function(opt) {
            opt.style.pointerEvents = 'none';
            if (opt.dataset.correct === '1') {
                opt.classList.add('correct');
            }
        });

        if (isCorrect) {
            btn.classList.add('correct');
            correctCount++;
        } else {
            btn.classList.add('incorrect');
        }

        var feedbackEl = document.getElementById('review-feedback');
        feedbackEl.style.display = 'block';
        if (isCorrect) {
            feedbackEl.className = 'duo-review-feedback duo-review-feedback--correct';
            feedbackEl.textContent = '¡Correcto!';
            feedbackEl.insertAdjacentHTML('afterbegin', '<span class="dashicons dashicons-yes" aria-hidden="true"></span> ');
        } else {
            feedbackEl.className = 'duo-review-feedback duo-review-feedback--incorrect';
            feedbackEl.textContent = 'Incorrecto. La respuesta correcta está marcada en verde.';
            feedbackEl.insertAdjacentHTML('afterbegin', '<span class="dashicons dashicons-no" aria-hidden="true"></span> ');
        }

        var nonce = document.getElementById('start-quick-review').dataset.nonce;
        var submitData = new FormData();
        submitData.append('action', 'amsawal_submit_review');
        submitData.append('nonce', nonce);
        submitData.append('item_key', q.item_key || 'h5p-' + (q.lesson_id || ''));
        submitData.append('correct', isCorrect ? '1' : '0');

        fetch(wpAmsawalReview.ajaxUrl, {
            method: 'POST',
            body: submitData,
            credentials: 'same-origin'
        }).catch(function(err) {
            console.error('Submit error:', err);
        });

        var nextBtn = document.getElementById('next-question');
        nextBtn.style.display = 'inline-flex';
        if (currentIndex >= currentQuestions.length - 1) {
            nextBtn.textContent = 'Ver resultados';
        } else {
            nextBtn.textContent = 'Siguiente →';
        }
    }

    function nextQuestion() {
        currentIndex++;
        if (currentIndex >= currentQuestions.length) {
            showSummary();
            return;
        }
        showQuestion();
    }

    function showSummary() {
        var questionEl = document.getElementById('review-question');
        var optionsEl = document.getElementById('review-options');
        var feedbackEl = document.getElementById('review-feedback');
        var nextBtn = document.getElementById('next-question');

        document.getElementById('review-progress-fill').style.width = '100%';

        questionEl.textContent = '¡Sesión completada!';

        optionsEl.innerHTML = '<div class="duo-review-summary">' +
            '<div class="duo-review-summary-score">' + correctCount + '/' + currentQuestions.length + '</div>' +
            '<div class="duo-review-summary-label">Respuestas correctas</div>' +
            '</div>';

        feedbackEl.style.display = 'none';
        nextBtn.style.display = 'inline-flex';
        nextBtn.textContent = 'Cerrar';
        nextBtn.onclick = function() {
            closeModal();
            location.reload();
        };
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
