
var links = [];

var fileXmlUrls = 
	[
		'xml_files/sitemap-products1.xml',
		'xml_files/sitemap-product01.xml',
		'xml_files/sitemap-product02.xml',
		'xml_files/sitemap-product03.xml',
		'xml_files/sitemap-product04.xml',
		'xml_files/sitemap-product05.xml',
		'xml_files/sitemap-product06.xml',
		'xml_files/sitemap-product07.xml',
		'xml_files/sitemap-product08.xml' 
	];

var casper = require('casper').create({
    pageSettings: {
        loadImages:  false,        // The WebPage instance used by Casper will
        loadPlugins: false         // use these settings
    },
    verbose: true,
    logLevel: 'debug'
});

var utils = require('utils');

function getLinks() {
    var links = document.querySelectorAll('loc');
    return Array.prototype.map.call(links, function(e) {
        return e.textContent;
    });
}

casper.renderJSON = function(what) {
    return this.echo(JSON.stringify(what, null, '  '));
};
 
casper.saveJSON = function(what) {
    fs.write('json/quotes.json', JSON.stringify(what, null, '  '), 'w');
};

 
// read sitemap-products.xml file =====================================
casper.start(fileXmlUrls[0], function() {
	links = this.evaluate(getLinks); 
});

casper.then(function() {
	this.each(links,function(i,link){
		this.thenOpen(link + '?setDevice=desktop',function(){
			
			var last_position_ = link.lastIndexOf("-");
			var last_position_dot = link.lastIndexOf(".");
			prod_id = link.substr(last_position_+1,last_position_dot - last_position_ -1);

			if(this.exists('#prod_title')){

				var prod_name = this.evaluate(function(){
					return $('#prod_title').text();
				});

				var prod_price_old = this.evaluate(function(){
					return $('#price_box').text().trim();
				});

				var prod_price_curr = this.evaluate(function(){
					return $('#special_price_box').text().trim();
				});

				var prod_price_host = this.evaluate(function(){
					return $('#mobile-price').text().trim();
				});

				var catalog_short_brief = this.evaluate(function(){
					return $('.prod_brief').text().trim();
				});

				var catalog_short_content = this.evaluate(function(){
					return $('.prod_details').html().trim();
				});

				var catalog_long = this.evaluate(function(){
					return $('#productDetails').html().trim();
				});
				
				//image product description
				var prod_desc = this.evaluate(function(prod_id){
					/*var imageTemp = $('#productDetails').find('.product-description__block img');
					var images = [];
					$.each(imageTemp,function(j,img){
						key = prod_id + "_desc_image_" + j;
						var tmp = {};
						tmp[key] = $(img).attr('data-original');
						images.push(tmp);
						$(img).replaceWith(key);
					});*/
					var content = $('#productDetails').find('.product-description__block').html();
					return  {
						content: content,
						// images: images
					};
				},prod_id);

				//image product
				var prod_images_link = this.evaluate(function(){
					var images = $('#productImageBox .prd-moreImages .productImage');
					var temp = [];
					$.each(images, function(i,img){
						temp.push($(img).attr('data-zoom-image'));
					});
					return temp;
				});
				
				//download image product
				var prod_images = [];
				for(var i = 0; i<prod_images_link.length; i++){
					var image_name_link = 'images/'+prod_id+'/product/'+prod_id + "_prod_image_" + i + '.jpg';
					this.download(prod_images_link[i],image_name_link);
					prod_images.push(image_name_link);	
				}

				var comments = this.evaluate(function(){
					var reviews = [];
					var $reviews = $('ul#js_reviews_list li.ratRev_reviewListRow');
					$.each($reviews,function(i,li){
						var temp = {
							title: $(li).find('.ratRev_revTitle').text().trim(),
							nick_name: $(li).find('.ratRev-revNickname').text().trim(),
							nick_name: $(li).find('.ratRev-revNickname').text().trim(),
							date: $(li).find('.ratRev_revDate').text().trim(),	
							status_review: $(li).find('.ratRev_revDetailsItm_badge').text().trim(),
							rating: $(li).find('ul.ratRev_ratOptions .itm-ratStars.itm-ratRating').width() / $(li).find('ul.ratRev_ratOptions .itm-ratStars.itm-ratRating').parent().width() * 100,
							content: $(li).find('.ratRev_revDetail').html().trim()
						};
						reviews.push(temp);
					});
					return reviews;
				});
				
				/*
				var comments = this.evaluate(function(){
					// var reviews = [];
					var dataParam = $('.ratRev-PagingWrapper-bottom a.ratRev-PagingItem').attr('data-params');					
					if(typeof dataParam != "undefined"){
						//get json from reviews data
						var data_link = 'http://www.lazada.vn/ajax/ratingreview/reviewspage?' + dataParam;
						var reviews = [];
						reviews = $.get(data_link, function(data){ 
							// return "abc";
						});
						reviews.done(function(data){

							return data;
						});
					}
					// return ;
				});*/
				
				//http://www.lazada.vn/ajax/ratingreview/reviewspage?page=1&sort=review_created_at&sortDirection=desc&sku=AP069ELAGER1VNAMZ
				var cat_link = this.evaluate(function(){
					var cat = [];
					var $cat = $('.header__breadcrumb .header__breadcrumb__wrapper ul li a');
					$.each($cat,function(i,a){
						var cat_name = $(a).attr('title');
						var href = $(a).attr('href');

						var href = href.split("/");
						var cat_alias = href[href.length-2];

						var temp = {};
						temp[cat_alias] = cat_name;
						cat.push(temp);
					});
					return cat;
				});

				var prod_status = this.evaluate(function(){
					var stock = $('#product-option-stock-number').text().trim();
					return stock;
				});

				var record = {
					product_id: prod_id,
					name : prod_name,
					price: {
						old	: prod_price_old,
						curr : prod_price_curr,
						hot	:  prod_price_host
					},
					catalog:{
						short: {
							brief: catalog_short_brief,
							content: catalog_short_content
						},
						long: catalog_long
					},
					description: {
						content: prod_desc.content,
						// images: prod_desc.images
					},
					images: prod_images,
					comments: comments,
					cat_link: cat_link,
					status: prod_status
				}

				//write record to json file
				this.saveJSON = function(what) {
				    stream = require('fs').open('export/data.json','w');
	                stream.writeLine(JSON.stringify(what, null, '  '));
	                stream.flush()
	               	stream.close();
				};
				this.saveJSON(record);

				var save_record = this.evaluate(function(record){
					//call php save record
					$.post('http://localhost/crawler_project/index.php/crawler/save'); 
				},record);
			}
		});
	});
});

casper.run();
