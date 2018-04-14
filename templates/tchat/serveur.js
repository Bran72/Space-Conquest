var http = require('http');

var port = 8080;
var tchat = '';

var server = http.createServer(function(req, res) {
  res.writeHead(200);
  res.write('OK !!!!!!!!');
  res.end();
});

var io = require('socket.io').listen(server);

io.sockets.on('connection', function(socket) {
  console.log('Un client est connecté !');

  socket.on('clientversserveur', function(texte) {
    tchat += texte + '<br>\n';
    io.sockets.emit('serveurversclient', tchat);
  });
});

server.listen(port);
console.log('On écoute sur le port '+port);
