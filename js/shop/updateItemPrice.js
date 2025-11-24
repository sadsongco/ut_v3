const updateItemPrice = (e) => {
  console.log(e.target.value);
  const url = '/functions/interface/shop/get_item_price.php?item_id=' + e.target.dataset.item_id + '&option_id=' + e.target.value;
  htmx.ajax('GET', url, { target: '#item' + e.target.dataset.item_id + '-price', swap: 'innerHTML' });
  // const price = parseFloat(e.target.options[e.target.selectedIndex].dataset.price);
  // document.getElementById('itemPrice').innerHTML = price.toFixed(2);
};
