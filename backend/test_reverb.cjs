const WebSocket = require('ws');
const ws = new WebSocket('ws://127.0.0.1:8080/app/my_reverb_key?protocol=7&client=js&version=8.3.0&flash=false');

ws.on('open', function open() {
  console.log('Connected to Reverb');
  ws.send(JSON.stringify({
    event: 'pusher:subscribe',
    data: { channel: 'pos-orders' }
  }));
});

ws.on('message', function incoming(data) {
  console.log('Received:', data.toString());
});

ws.on('error', function error(err) {
  console.log('Error:', err);
});
