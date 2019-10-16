numberofGoats = input ("How many goats?")
numberofGoats = int (numberofGoats)

numberofApples = input ("How many apples?")
numberofApples = int (numberofApples)

applesPerGoat = (numberofApples // numberofGoats) 
print ("Each goat will receive", applesPerGoat, "apples.")

leftoverApples = (numberofApples % numberofGoats)
print (leftoverApples, "apples will be left in the basket.")