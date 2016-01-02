import requests
import md5
import sys
import json
import logging

#URL = "http://api.momo.im"
URL = "http://192.168.99.100:8080"


headers = {
    "Content-Type":"application/json"
}


obj = {
    "mobile":"13800000000",
    "zone_code":"86",
    "password":"123456",
}


url = URL + "/user/login"
print url
resp = requests.post(url, data=json.dumps(obj), headers=headers)

assert(resp.status_code == 200)
r = json.loads(resp.content)
token = r["access_token"]
print "token:", token


headers = {
    "Content-Type":"application/json",
    "Authorization":"Bearer " + token
}


url = URL + "/photo/bp_upload.json"
f = open("test.jpg", "rb")
content = f.read()
f.close()
print "file len:", len(content)
m = md5.new(content).hexdigest()

obj = {"md5":m, "size":len(content)}
print obj
resp = requests.post(url, data=json.dumps(obj), headers = headers)

assert(resp.status_code == 200)
r = json.loads(resp.content)
print r

if r["uploaded"]:
    image_url = r["src"]
else:
    upload_id = r["upload_id"]

    headers = {
        "Authorization":"Bearer " + token
    }

    url = URL + "/photo/bp_upload.json?upload_id=%s&offset=0"%upload_id
    files = {'file': ("test.jpg", content)}
    resp = requests.post(url, files=files, headers=headers)
    print resp.content
    print resp.status_code
    assert(resp.status_code == 200)
    r = json.loads(resp.content)
    image_url = r["src"]

print image_url

headers = {
    "Authorization":"Bearer " + token
}

resp = requests.get(image_url, headers=headers)

assert(resp.status_code == 200)

f = open("test2.jpg", "wb")
f.write(resp.content)
f.close()
