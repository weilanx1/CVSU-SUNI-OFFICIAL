document.addEventListener("DOMContentLoaded", function () {
    // 1. Hanapin ang profile image gamit ang class nito mula sa iyong PHP code
    const profileImg = document.querySelector('nav ul li img.profile');
    
    if (profileImg) {
        // Kunin ang magulang na <li> element
        const parentLi = profileImg.parentElement;
        
        // 2. I-set ang magulang para maging relative container
        parentLi.style.position = 'relative';
        parentLi.style.display = 'inline-block';
        parentLi.style.listStyle = 'none';
        
        // Kunin ang kasalukuyang src ng profile picture para magamit din sa loob ng card
        const currentAvatarSrc = profileImg.getAttribute('src');
        
        // 3. I-inject ang mas maliit at mas siksik na dropdown container
        const dropdownHTML = `
            <div class="profile-dropdown-menu" id="userDropdownMenu" style="
                display: none;
                position: absolute;
                right: 0;
                top: 50px;
                background-color: #ffffff;
                min-width: 230px;
                box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.08);
                border-radius: 16px;
                z-index: 9999;
                padding: 0px 0 10px 0;
                font-family: 'Poppins', sans-serif;
                text-align: left;
                box-sizing: border-box;
                border: 1px solid #eef0f2;
            ">
                <div class="dropdown-user-card" style="
                    display: flex; 
                    align-items: center; 
                    gap: 12px; 
                    padding: 0 16px 10px 16px;
                    width: 100%;
                    box-sizing: border-box;
                ">

        
                </div>
                
                
                
                <a href="sign-in.php" class="sign-out-item" id="signOutLink" style="
                    color: #fffff !important;
                    padding: 10px 16px;
                    text-decoration: none !important;
                    display: flex !important;
                    flex-direction: row !important;
                    align-items: center;
                    font-size: 14px;
                    font-weight: 500;
                    width: 100%;
                    box-sizing: border-box;
                    transition: background 0.2s, color 0.2s;
                " onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.color='#ff453a' !important;" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#2d2d2d' !important;">
                    Sign Out
                </a>
            </div>
        `;
        
        // 4. Isaksak ang nilikhang HTML block sa tabi ng profile image
        parentLi.insertAdjacentHTML('beforeend', dropdownHTML);
        
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const signOutLink = document.getElementById('signOutLink');
        
        // 5. Click handler para sa profile icon toggle action
        profileImg.addEventListener('click', function (e) {
            e.stopPropagation(); 
            
            const isClosed = dropdownMenu.style.display === 'none';
            
            if (isClosed) {
                dropdownMenu.style.setProperty('display', 'flex', 'important');
                dropdownMenu.style.setProperty('flex-direction', 'column', 'important');
            } else {
                dropdownMenu.style.display = 'none';
            }
        });
        
        // 6. Confirmation Prompt para sa Sign Out bago lumipat sa sign-in.php
        signOutLink.addEventListener('click', function (e) {
            const confirmLogout = confirm("Are you sure you want to sign out?");
            if (!confirmLogout) {
                e.preventDefault(); // Pipigilan ang paglipat ng page kapag Cancel ang pinindot
            }
        });
        
        // 7. Isara ang dropdown kapag may pinindot na ibang bahagi ng screen
        document.addEventListener('click', function (e) {
            if (!dropdownMenu.contains(e.target) && e.target !== profileImg) {
                dropdownMenu.style.display = 'none';
            }
        });
    }
});