const t=document.getElementById('menuToggle');
const n=document.getElementById('nav');
if(t&&n){
  t.addEventListener('click',()=>n.classList.toggle('show'));
  n.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>n.classList.remove('show')));
}

// Navbar behavior:
// - home-page: transparent default, solid after scroll
// - inner-page: always solid
const header=document.querySelector('.site-header');
const isHome = document.body.classList.contains('home-page');

const applyScrolled=()=>{
  if(!header) return;
  if(!isHome){
    header.classList.add('scrolled');
    return;
  }
  if(window.scrollY > 40) header.classList.add('scrolled');
  else header.classList.remove('scrolled');
};
window.addEventListener('scroll', applyScrolled, {passive:true});
window.addEventListener('pageshow', applyScrolled);
window.addEventListener('load', applyScrolled);
applyScrolled();
