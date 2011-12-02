#### Introduction

A curl wrapper for Yii, allows easier access to curl functions.

#### Example usage

To grab a url:
<pre>
$curl = new ACurl();
$data = $curl->get("http://www.google.com/")->data;
echo $data;
</pre>

To retrieve just the headers for a URL:
<pre>
$curl = new ACurl();
$headers = $curl->head("http://www.google.com/")->headers;
print_r($headers);
</pre>

To post data to a URL:
<pre>
$curl = new ACurl();
$response = $curl->post("http://example.com/",array("key" => "value"))->data;
echo $response;
</pre>

To load JSON from a URL:

<pre>
$curl = new ACurl();
$response = $curl->get("http://www.example.com/test.json")->fromJSON();
print_r($response);
</pre>