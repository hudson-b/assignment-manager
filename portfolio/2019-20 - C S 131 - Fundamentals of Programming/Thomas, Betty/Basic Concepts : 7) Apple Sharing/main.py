goats = int(input ("How many goats?"))

apples = int(input ("How many apples?"))

receive = (apples // goats)

leftOver = (apples % goats)

print ("Each goat will receive", receive, "apples.")

print (leftOver, "apples will be left in the basket.")