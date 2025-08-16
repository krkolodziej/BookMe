document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar-sticky');
    if (!sidebar) return;
    
    const sidebarParent = sidebar.parentElement;
    const sidebarTop = sidebar.getBoundingClientRect().top + window.pageYOffset;
    const sidebarOriginalHeight = sidebar.offsetHeight;

    
    const sidebarWidth = sidebarParent.offsetWidth;
    sidebar.style.width = sidebarWidth + 'px';

    
    const endMarker = document.getElementById('content-end');
    document.querySelector('.container').after(endMarker);

    function updateSidebar() {
        const scrollTop = window.pageYOffset;
        const windowHeight = window.innerHeight;
        const mainContent = document.querySelector('.col-md-8');

        
        const mainContentEnd = mainContent.offsetTop + mainContent.offsetHeight;

        
        const endBuffer = 30; // Procent wysokości ekranu
        const bufferPixels = (windowHeight * endBuffer) / 100;

        
        const maxSidebarBottom = mainContentEnd - bufferPixels;

        if (sidebarOriginalHeight < windowHeight) {
            
            if (scrollTop > sidebarTop) {
                sidebar.style.position = 'fixed';
                sidebar.style.top = '20px';
                sidebar.style.width = sidebarWidth + 'px';

                
                const currentSidebarBottom = scrollTop + sidebar.offsetHeight + 20;
                if (currentSidebarBottom > maxSidebarBottom) {
                    
                    const adjustedTop = maxSidebarBottom - sidebar.offsetHeight - scrollTop;
                    sidebar.style.top = adjustedTop + 'px';
                }
            } else {
                sidebar.style.position = 'static';
                sidebar.style.width = sidebarWidth + 'px';
            }
        } else {
            
            const maxHeight = windowHeight - 40; // 20px z góry i 20px z dołu

            if (scrollTop > sidebarTop) {
                sidebar.style.position = 'fixed';
                sidebar.style.top = '20px';
                sidebar.style.width = sidebarWidth + 'px';
                sidebar.style.maxHeight = maxHeight + 'px';
                sidebar.style.overflowY = 'auto';

                
                const currentSidebarBottom = scrollTop + maxHeight + 20;
                if (currentSidebarBottom > maxSidebarBottom) {
                    
                    const adjustedTop = maxSidebarBottom - maxHeight - scrollTop;
                    sidebar.style.top = adjustedTop + 'px';
                }
            } else {
                sidebar.style.position = 'static';
                sidebar.style.width = sidebarWidth + 'px';
                sidebar.style.maxHeight = 'none';
                sidebar.style.overflowY = 'visible';
            }
        }
    }

    
    window.addEventListener('resize', function() {
        
        if (sidebar.style.position !== 'fixed') {
            const newSidebarWidth = sidebarParent.offsetWidth;
            sidebar.style.width = newSidebarWidth + 'px';
        }
        updateSidebar();
    });

    window.addEventListener('scroll', updateSidebar);
    updateSidebar();
});