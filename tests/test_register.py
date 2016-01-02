import requests
import md5
import sys
import json
import logging

#URL = "http://api.momo.im"
URL = "http://192.168.99.100:8080"


mobile = "13800000002"

obj = {
    "mobile":mobile,
    "zone_code":"86",
}
headers = {
    "Content-Type":"application/json"
}

url = URL + "/auth/verify_code.json"

resp = requests.post(url, data=json.dumps(obj), headers=headers)
print resp.content
assert(resp.status_code == 200)
r = json.loads(resp.content)


url = URL + "/auth/token"
obj = {
    "mobile":mobile,
    "zone_code":"86",
    "code":"111111",
}
resp = requests.post(url, data=json.dumps(obj), headers=headers)
assert(resp.status_code == 200)
r = json.loads(resp.content)
print r
token = r["access_token"]


headers = {
    "Content-Type":"application/json",
    "Authorization":"Bearer " + token
}

url = URL + "/user/init"
obj = {
    "username":"test",
    "password":"123456",
}
resp = requests.post(url, data=json.dumps(obj), headers=headers)
assert(resp.status_code == 200)


headers = {
    "Content-Type":"application/json"
}

obj = {
    "mobile":mobile,
    "zone_code":"86",
    "password":"123456",
}


url = URL + "/user/login"
print url
resp = requests.post(url, data=json.dumps(obj), headers=headers)

assert(resp.status_code == 200)
r = json.loads(resp.content)
print r

