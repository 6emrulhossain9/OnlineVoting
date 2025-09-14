document.addEventListener('DOMContentLoaded', () => {
    const welcomeUser = document.getElementById('welcome-user');
    const candidatesList = document.getElementById('candidates-list');
    const votingMessage = document.getElementById('voting-message');
    const logoutBtn = document.getElementById('logout-btn');

    // Check if user is authenticated and is a 'user' role
    fetch('check_auth.php?role=user')
        .then(response => {
            if (!response.ok) {
                window.location.href = 'index.html';
            }
            return response.json();
        })
        .then(data => {
            welcomeUser.textContent = data.username;
            fetchCandidates(data.is_voted);
        })
        .catch(() => {
            window.location.href = 'index.html';
        });

    function fetchCandidates(isVoted) {
        candidatesList.innerHTML = '';
        fetch('get_candidates.php')
            .then(response => response.json())
            .then(candidates => {
                candidates.forEach(candidate => {
                    const candidateDiv = document.createElement('div');
                    candidateDiv.className = 'candidate';
                    candidateDiv.innerHTML = `
                        <span class="candidate-name">${candidate.name}</span>
                        ${isVoted ? `<span class="vote-count">${candidate.votes} Votes</span>` : `<button class="vote-btn" data-id="${candidate.id}">Vote</button>`}
                    `;
                    candidatesList.appendChild(candidateDiv);
                });

                if (isVoted) {
                    votingMessage.textContent = "You have already voted! Here are the results.";
                } else {
                    votingMessage.textContent = "Click on a candidate to vote.";
                    addVoteListeners();
                }
            });
    }

    function addVoteListeners() {
        document.querySelectorAll('.vote-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const candidateId = e.target.dataset.id;
                const formData = new FormData();
                formData.append('candidate_id', candidateId);

                fetch('vote.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    votingMessage.textContent = text;
                    if (text.includes("successful")) {
                        fetchCandidates(true);
                    }
                });
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