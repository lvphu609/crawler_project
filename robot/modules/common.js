/*
	read products url of xml file
*/
function readFileXml($file){
	var fs = require('fs');
	var linkProduct = [];
	stream = fs.open($file, 'r');
	line = stream.readLine();
	i = 0;
	while(line) {
		line = stream.readLine();
		if(line.indexOf('<loc>') != -1)
		{
			var link = line.replace("<loc>","").replace("</loc>","");
			linkProduct.push(link);
		}
		i++;
	}
	return linkProduct;
}
exports.readFileXml = readFileXml;
