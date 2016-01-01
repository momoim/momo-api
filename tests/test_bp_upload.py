import requests
import md5
import sys
import json
import logging

URL = "http://192.168.99.100:8080"


obj = {
    "mobile":"13800000000",
    "zone_code":"86",
    "password":"123456",
}

headers = {
    "Content-Type":"application/json"
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

#url = URL + "/statuses/index.json"
#resp = requests.get(url, headers=headers)
#print resp.content
#assert(resp.status_code == 200)
#print resp.content


url = URL + "/photo/bp_upload.json"
f = open("test2.png", "rb")
content = f.read()
f.close()

m = md5.new(content).hexdigest()

obj = {"md5":m, "size":len(content)}
print obj
resp = requests.post(url, data=json.dumps(obj), headers = headers)



print resp.content
assert(resp.status_code == 200)
