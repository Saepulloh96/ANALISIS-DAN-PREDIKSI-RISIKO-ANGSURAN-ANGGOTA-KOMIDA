// Koperasi Mitra Dhuafa Risk Predictor App - Interactive Scripts

document.addEventListener("DOMContentLoaded", function() {
    console.log("KOMIDA Risk Predictor loaded successfully.");
    
    // Add dynamic animations to elements on hover
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });

    // Form confirmation for adding members
    const addMemberForm = document.querySelector('form[action="members.php"]');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function(e) {
            const idInput = document.getElementById('id_anggota');
            const nameInput = document.getElementById('nama');
            if (idInput.value.trim() === "" || nameInput.value.trim() === "") {
                e.preventDefault();
                alert("Mohon lengkapi ID Anggota dan Nama Anggota.");
            }
        });
    }
});
