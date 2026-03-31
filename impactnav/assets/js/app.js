// impactNav — frontend helpers

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});

// Category checkbox visual toggle
document.querySelectorAll('.cat-checkbox input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', function () {
        this.closest('.cat-checkbox').classList.toggle('checked', this.checked);
    });
});

// Skills input: show tag-like preview
const skillsInput = document.getElementById('skills');
if (skillsInput) {
    skillsInput.addEventListener('keydown', function (e) {
        if (e.key === ',') {
            setTimeout(() => {
                this.value = this.value.replace(/,\s*$/, ', ');
            }, 0);
        }
    });
}
