document.addEventListener('DOMContentLoaded', () => {
    const addCandidateForm = document.getElementById('add-candidate-form');
    const addCandidateMessage = document.getElementById('add-candidate-message');
    const resultsList = document.getElementById('results-list');
    const logoutBtn = document.getElementById('logout-btn');

    // Check if user is authenticated and is an 'admin' role
    fetch('check_auth.php?role=admin')
        .then(response => {
            if (!response.ok) {
                window.location.href = 'index.html';
            }
            return response.json();
        })
        .then(() => {
            fetchResults();
        })
        .catch(() => {
            window.location.href = 'index.html';
        });

    addCandidateForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const candidateName = document.getElementById('candidate-name').value;
        const formData = new FormData();
        formData.append('candidate_name', candidateName);

        fetch('add_candidate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            addCandidateMessage.textContent = text;
            addCandidateForm.reset();
            fetchResults();
        });
    });

    function fetchResults() {
        resultsList.innerHTML = '';
        fetch('get_results.php')
            .then(response => response.json())
            .then(candidates => {
                candidates.forEach(candidate => {
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'result-item';
                    resultDiv.innerHTML = `
                        <div class="result-candidate">
                            <i class="fas fa-user"></i>
                            <span>${candidate.name}</span>
                        </div>
                        <div>${candidate.votes} Votes</div>
                    `;
                    resultsList.appendChild(resultDiv);
                });
            });
    }

    logoutBtn.addEventListener('click', () => {
        fetch('logout.php')
            .then(() => {
                window.location.href = 'index.html';
            });
    });
});
