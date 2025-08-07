document.addEventListener('DOMContentLoaded', function(){
    const useLinks = document.querySelectorAll('.pm-use-prompt');
    useLinks.forEach(link => {
        link.addEventListener('click', function(e){
            e.preventDefault();
            const json = this.getAttribute('data-json');
            navigator.clipboard.writeText(json).then(function() {
                console.log('Prompt JSON copied to clipboard');
                window.open('https://www.chatgpt.com', '_blank');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        });
    });
});
