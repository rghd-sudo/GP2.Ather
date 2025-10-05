const items = document.querySelectorAll('.item');

items.forEach(item => {
  const checkbox = item.querySelector('input[type="checkbox"]');
  checkbox.addEventListener('change', () => {
    const type = item.dataset.type;
    console.log(type + ' is ' + (checkbox.checked ? 'ON' : 'OFF'));
    
  });
});