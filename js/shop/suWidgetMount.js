const showPayment = (checkoutId) => {
  SumUpCard.mount({
    id: 'sumup-card',
    checkoutId: checkoutId,
    onResponse: function (type, body) {
      console.log('Type', type);
      console.log('Body', body);
    },
  });
};
